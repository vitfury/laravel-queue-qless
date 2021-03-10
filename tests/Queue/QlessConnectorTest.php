<?php

namespace LaravelQless\Tests\Queue;

use LaravelQless\LaravelQlessServiceProvider;
use LaravelQless\Queue\QlessConnector;
use LaravelQless\Queue\QlessQueue;
use PHPUnit\Framework\TestCase;
use Qless\Config;

class QlessConnectorTest extends TestCase
{

    #region Test data
    private const QLESS_CONFIG = [
        'driver' => 'qless',
        'connection' => 'qless',
        'queue' => 'default',
        'redis_connection' => 'qless',
    ];

    private const HOST1 = 'redis.host';
    private const PORT1 = 3333;
    private const PWD1 = 'PASSWD';
    private const DB1 = 1;

    private const HOST2 = 'redis.host2';
    private const PORT2 = 4333;
    private const PWD2 = 'PASSWD2';
    private const DB2 = 2;

    private const HOST1_CONFIG = [
        'host' => self::HOST1,
        'password' => self::PWD1,
        'port' => self::PORT1,
        'database' => self::DB1,
    ];

    private const HOST2_CONFIG = [
        'host' => self::HOST2,
        'password' => self::PWD2,
        'port' => self::PORT2,
        'database' => self::DB2,
    ];

    private const SINGLE_REDIS_CONFIG = self::HOST1_CONFIG;
    private const SHARDING_REDIS_CONFIG_SINGLE = self::HOST2_CONFIG;

    private const SHARDING_REDIS_CONFIG_MULTIPLY = [
        'host' => self::HOST1 . ',' . self::HOST2,
        'password' => self::PWD1 . ',' . self::PWD2,
        'port' => self::PORT1 . ',' . self::PORT2,
        'database' => self::DB1 . ',' . self::DB2,
    ];

    private const SHARDING_REDIS_CONFIG_MULTIPLY_SPACES = [
        'host' => '  ' . self::HOST1 . ' , ' . self::HOST2 . '   ',
        'password' => '   ' . self::PWD1 . '   , ' . self::PWD2 . '   ',
        'port' => '   ' . self::PORT1 . ' , ' . self::PORT2 . '   ',
        'database' => '  ' . self::DB1 . ' , ' . self::DB2 . '   ',
    ];
    #endregion

    protected function setEnv(array $redisConfig)
    {
        $app = [
            'config' => new Config(),
        ];
        $app['config']->set('queue.default', 'qless');
        $app['config']->set('queue.connections.qless', self::QLESS_CONFIG);
        $app['config']->set('database.redis.qless', $redisConfig);

        $providerMock = new LaravelQlessServiceProvider($app);
        $providerMock->boot();
    }

    protected function compareConfigs(array $expectedConfig, Config $resultingConfig)
    {
        $this->assertEquals($expectedConfig['host'], $resultingConfig->get('host'));
        $this->assertEquals($expectedConfig['port'], $resultingConfig->get('port'));
        $this->assertEquals($expectedConfig['password'], $resultingConfig->get('password'));
        $this->assertEquals($expectedConfig['database'], $resultingConfig->get('database'));
    }

    protected function getExpectedConfigOfSelected(Config $resultConfig): array
    {
        switch ($resultConfig->get('host')) {
            case self::HOST1:
            {
                return self::HOST1_CONFIG;
            }
            case self::HOST2:
            {
                return self::HOST2_CONFIG;
            }
            default:
                $this->fail('Invalid host config');
        }
    }


    public function testConnectSingleConfig()
    {
        $config = self::SINGLE_REDIS_CONFIG;

        $this->setEnv($config);

        $connector = new QlessConnector();
        $queue = $connector->connect(self::QLESS_CONFIG);

        $this->assertInstanceOf(QlessQueue::class, $queue);

        $qlessConfig = $queue->getConnection()->getConfig();
        $this->compareConfigs($config, $qlessConfig);
    }

    public function testConnectShardingSingleConfig()
    {
        $config = self::SHARDING_REDIS_CONFIG_SINGLE;

        $this->setEnv($config);

        $connector = new QlessConnector();
        $queue = $connector->connect(self::QLESS_CONFIG);

        $this->assertInstanceOf(QlessQueue::class, $queue);

        $qlessConfig = $queue->getConnection()->getConfig();
        $this->compareConfigs($config, $qlessConfig);
    }


    public function testConnectShardingMultiplyConfig()
    {
        $config = self::SHARDING_REDIS_CONFIG_MULTIPLY;

        $this->setEnv($config);

        $connector = new QlessConnector();
        $queue = $connector->connect(self::QLESS_CONFIG);

        $this->assertInstanceOf(QlessQueue::class, $queue);

        $qlessConfig = $queue->getConnection()->getConfig();

        $excpectedConfig = $this->getExpectedConfigOfSelected($qlessConfig);
        $this->compareConfigs($excpectedConfig, $qlessConfig);
    }


    public function testConnectShardingMultiplyConfigWithSpaces()
    {
        $config = self::SHARDING_REDIS_CONFIG_MULTIPLY_SPACES;

        $this->setEnv($config);

        $connector = new QlessConnector();
        $queue = $connector->connect(self::QLESS_CONFIG);

        $this->assertInstanceOf(QlessQueue::class, $queue);

        $qlessConfig = $queue->getConnection()->getConfig();

        $excpectedConfig = $this->getExpectedConfigOfSelected($qlessConfig);

        $this->compareConfigs($excpectedConfig, $qlessConfig);
    }


    public function testConnectShardingMultiplyConfigNoPassword()
    {
        $config = self::SHARDING_REDIS_CONFIG_MULTIPLY;
        $config['password'] = null;


        $this->setEnv($config);

        $connector = new QlessConnector();
        $queue = $connector->connect(self::QLESS_CONFIG);

        $this->assertInstanceOf(QlessQueue::class, $queue);

        $qlessConfig = $queue->getConnection()->getConfig();

        $excpectedConfig = $this->getExpectedConfigOfSelected($qlessConfig);
        $excpectedConfig['password'] = QlessConnector::DEFAULT_PASSWORD;

        $this->compareConfigs($excpectedConfig, $qlessConfig);
    }

    public function testConnectShardingMultiplyConfigNoPort()
    {
        $config = self::SHARDING_REDIS_CONFIG_MULTIPLY;
        $config['port'] = null;

        $this->setEnv($config);

        $connector = new QlessConnector();
        $queue = $connector->connect(self::QLESS_CONFIG);

        $this->assertInstanceOf(QlessQueue::class, $queue);

        $qlessConfig = $queue->getConnection()->getConfig();
        $excpectedConfig = $this->getExpectedConfigOfSelected($qlessConfig);
        $excpectedConfig['port'] = QlessConnector::DEFAULT_PORT;


        $this->compareConfigs($excpectedConfig, $qlessConfig);
    }

    public function testConnectShardingMultiplyConfigNoDatabase()
    {
        $config = self::SHARDING_REDIS_CONFIG_MULTIPLY;
        $config['database'] = null;

        $this->setEnv($config);

        $connector = new QlessConnector();
        $queue = $connector->connect(self::QLESS_CONFIG);

        $this->assertInstanceOf(QlessQueue::class, $queue);

        $qlessConfig = $queue->getConnection()->getConfig();
        $excpectedConfig = $this->getExpectedConfigOfSelected($qlessConfig);
        $excpectedConfig['database'] = QlessConnector::DEFAULT_DATABASE;


        $this->compareConfigs($excpectedConfig, $qlessConfig);
    }

    public function testConnectShardingMultiplyConfigSingleValues()
    {
        $config = self::SHARDING_REDIS_CONFIG_MULTIPLY;
        $config['password'] = self::PWD1;
        $config['port'] = self::PORT1;
        $config['database'] = self::DB1;

        $this->setEnv($config);

        $connector = new QlessConnector();
        $queue = $connector->connect(self::QLESS_CONFIG);

        $this->assertInstanceOf(QlessQueue::class, $queue);

        $qlessConfig = $queue->getConnection()->getConfig();

        switch ($qlessConfig->get('host')) {
            case self::HOST1:
            {
                $excpectedConfig = self::HOST1_CONFIG;
                break;
            }
            case self::HOST2:
            {
                $excpectedConfig = self::HOST2_CONFIG;
                $excpectedConfig['password'] = QlessConnector::DEFAULT_PASSWORD;
                $excpectedConfig['port'] = QlessConnector::DEFAULT_PORT;
                $excpectedConfig['database'] = QlessConnector::DEFAULT_DATABASE;
                break;
            }
            default:
                $this->fail('Invalid host config');
        }

        $this->compareConfigs($excpectedConfig, $qlessConfig);
    }

}