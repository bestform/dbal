<?php

namespace Doctrine\Tests\DBAL;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Driver\DriverException as InnerDriverException;
use Doctrine\Tests\DbalTestCase;
use Doctrine\DBAL\Driver;

class DBALExceptionTest extends DbalTestCase
{
    public function testDriverExceptionDuringQueryAcceptsBinaryData()
    {
        /* @var $driver Driver */
        $driver = $this->createMock(Driver::class);
        $e = DBALException::driverExceptionDuringQuery($driver, new \Exception, '', array('ABC', chr(128)));
        $this->assertContains('with params ["ABC", "\x80"]', $e->getMessage());
    }

    public function testAvoidOverWrappingOnDriverException()
    {
        /* @var $driver Driver */
        $driver = $this->createMock(Driver::class);
        $inner = new class extends \Exception implements InnerDriverException
        {
            /**
             * {@inheritDoc}
             */
            public function getErrorCode()
            {
            }

            /**
             * {@inheritDoc}
             */
            public function getSQLState()
            {
            }
        };
        $ex = new DriverException('', $inner);
        $e = DBALException::driverExceptionDuringQuery($driver, $ex, '');
        $this->assertSame($ex, $e);
    }

    public function testDriverRequiredWithUrl()
    {
        $url = 'mysql://localhost';
        $exception = DBALException::driverRequired($url);

        $this->assertInstanceOf(DBALException::class, $exception);
        $this->assertSame(
            sprintf(
                "The options 'driver' or 'driverClass' are mandatory if a connection URL without scheme " .
                'is given to DriverManager::getConnection(). Given URL: %s',
                $url
            ),
            $exception->getMessage()
        );
    }
}
