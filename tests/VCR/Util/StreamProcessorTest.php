<?php

namespace VCR\Util;

use VCR\Configuration;
use lapistano\ProxyObject\ProxyBuilder;

class StreamProcessorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider streamOpenAppendFilterProvider
     * @param  boolean $expected
     * @param  boolean $shouldProcess
     * @param  integer $option
     */
    public function testStreamOpenShouldAppendFilters($expected, $option, $shouldProcess = null)
    {
        $mock = $this->getMockBuilder('VCR\Util\StreamProcessor')
            ->disableOriginalConstructor()
            ->setMethods(array('intercept', 'restore', 'appendFiltersToStream', 'shouldProcess'))
            ->getMock();

        if (!is_null($shouldProcess)) {
            $mock->expects($this->once())->method('shouldProcess')->will($this->returnValue($shouldProcess));
        }

        if ($expected) {
            $mock->expects($this->once())->method('appendFiltersToStream');
        } else {
            $mock->expects($this->never())->method('appendFiltersToStream');
        }

        $mock->stream_open('tests/fixtures/streamprocessor_data', 'r', $option, $fullPath);
        $mock->stream_close();
    }

    public function streamOpenAppendFilterProvider()
    {
        return array(
            array(true, StreamProcessor::STREAM_OPEN_FOR_INCLUDE, true),
            array(false, StreamProcessor::STREAM_OPEN_FOR_INCLUDE, false),
            array(false, 0),
        );
    }

    public function testUrlStatSuccessfully()
    {
        $test = $this;
        set_error_handler(function($errno, $errstr, $errfile, $errline) use ($test) {
            $test->fail('should not throw errors');
        });

        $processor = new StreamProcessor();
        $processor->url_stat('tests/fixtures/streamprocessor_data');

        restore_error_handler();
    }

    public function testUrlStatFileNotFoundExpectNoException()
    {
        $test = $this;
        set_error_handler(function($errno, $errstr, $errfile, $errline) use ($test) {
            throw new \ErrorException($errstr, $errno, $errno, $errfile, $errline);
        });

        $processor = new StreamProcessor();
        $processor->url_stat('file_not_found');

        restore_error_handler();
    }

    public function testDirOpendir()
    {
        $processor = new StreamProcessor();
        $this->assertTrue($processor->dir_opendir('tests/fixtures'));
        $processor->dir_closedir();
    }

    public function testDirOpendirNotFound()
    {
        $test = $this;
        set_error_handler(function($errno, $errstr, $errfile, $errline) use ($test) {
            $test->assertEquals(
                'opendir(not_found): failed to open dir: No such file or directory',
                $errstr
            );
        });

        $processor = new StreamProcessor();
        $this->assertFalse($processor->dir_opendir('not_found'));

        restore_error_handler();
    }

    public function testMakeDir()
    {
        $mock = $this->getStreamProcessorMock();
        $mock->expects($this->exactly(2))->method('restore');
        $mock->expects($this->exactly(2))->method('intercept');

        $this->assertTrue($mock->mkdir('tests/fixtures/unittest_streamprocessor', 0777, false));
        $this->assertTrue($mock->rmdir('tests/fixtures/unittest_streamprocessor'));
    }

    public function testRename()
    {
        $mock = $this->getStreamProcessorMock();
        $mock->expects($this->exactly(3))->method('restore');
        $mock->expects($this->exactly(3))->method('intercept');

        $this->assertTrue($mock->mkdir('tests/fixtures/unittest_streamprocessor', 0777, false));
        $this->assertTrue($mock->rename('tests/fixtures/unittest_streamprocessor', 'tests/fixtures/sp'));
        $this->assertTrue($mock->rmdir('tests/fixtures/sp'));
    }

    public function testStreamMetadata()
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $this->markTestSkipped('Behavior is only applicable and testable for PHP 5.4+');
        }

        $mock = $this->getStreamProcessorMock();
        $mock->expects($this->exactly(8))->method('restore');
        $mock->expects($this->exactly(8))->method('intercept');

        $path = 'tests/fixtures/unnitest_streamprocessor_metadata';
        $this->assertTrue($mock->stream_metadata($path, STREAM_META_TOUCH, null));
        $this->assertTrue($mock->stream_metadata($path, STREAM_META_TOUCH, array(time(), time())));

        $this->assertTrue($mock->stream_metadata($path, STREAM_META_OWNER_NAME, posix_getuid()));
        $this->assertTrue($mock->stream_metadata($path, STREAM_META_OWNER, posix_getuid()));

        $this->assertTrue($mock->stream_metadata($path, STREAM_META_GROUP_NAME, posix_getgid()));
        $this->assertTrue($mock->stream_metadata($path, STREAM_META_GROUP, posix_getgid()));

        $this->assertTrue($mock->stream_metadata($path, STREAM_META_ACCESS, 0777));

        $this->assertTrue($mock->unlink($path));
    }

    protected function getStreamProcessorMock()
    {
        return $this->getMockBuilder('VCR\Util\StreamProcessor')
            ->disableOriginalConstructor()
            ->setMethods(array('intercept', 'restore'))
            ->getMock();
    }

    // /**
    //  * @dataProvider isWhitelistedProvider
    //  * @param  boolean $expected
    //  * @param  array $whitelist
    //  * @param  string $uri
    //  */
    // public function testIsWhitelisted($expected, $whitelist, $uri)
    // {
    //     $config = new Configuration();
    //     $config->setWhitelist($whitelist);

    //     $proxy = new ProxyBuilder('\VCR\Util\StreamProcessor');
    //     StreamProcessor::$configuration = null;
    //     $processor = $proxy
    //         ->disableOriginalConstructor()
    //         ->setProperties(array('configuration'))
    //         ->setMethods(array('isWhitelisted'))
    //         ->getProxy();

    //     $this->assertEquals($expected, $processor->isWhitelisted($uri));
    // }

    // public function isWhitelistedProvider()
    // {
    //     return array(
    //         array(true, array('some/test/dir'), 'some/test/dir/testfile'),
    //         array(true, array('some/test/dir'), 'some/test/dir/testfile'),
    //     );
    // }
}
