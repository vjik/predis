<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-zset
 */
class ZRANGEBYSCORE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\ZRANGEBYSCORE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZRANGEBYSCORE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $modifiers = array(
            'withscores' => true,
            'limit' => array(0, 100),
        );

        $arguments = array('zset', 0, 100, $modifiers);
        $expected = array('zset', 0, 100, 'LIMIT', 0, 100, 'WITHSCORES');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithStringWithscores(): void
    {
        $arguments = array('zset', 0, 100, 'withscores');
        $expected = array('zset', 0, 100, 'WITHSCORES');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithNamedLimit(): void
    {
        $arguments = array('zset', 0, 100, array('limit' => array('offset' => 1, 'count' => 2)));
        $expected = array('zset', 0, 100, 'LIMIT', 1, 2);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = array('element1', 'element2', 'element3');
        $expected = array('element1', 'element2', 'element3');

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group disconnected
     */
    public function testParseResponseWithScores(): void
    {
        $raw = array('element1', '1', 'element2', '2', 'element3', '3');
        $expected = array('element1' => '1', 'element2' => '2', 'element3' => '3');

        $command = $this->getCommandWithArgumentsArray(array('zset', 0, 1, 'withscores'));

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group disconnected
     */
    public function testAddsWithscoresModifiersOnlyWhenOptionIsTrue(): void
    {
        $command = $this->getCommandWithArguments('zset', 0, 100, array('withscores' => true));
        $this->assertSame(array('zset', 0, 100, 'WITHSCORES'), $command->getArguments());

        $command = $this->getCommandWithArguments('zset', 0, 100, array('withscores' => 1));
        $this->assertSame(array('zset', 0, 100, 'WITHSCORES'), $command->getArguments());

        $command = $this->getCommandWithArguments('zset', 0, 100, array('withscores' => false));
        $this->assertSame(array('zset', 0, 100), $command->getArguments());

        $command = $this->getCommandWithArguments('zset', 0, 100, array('withscores' => 0));
        $this->assertSame(array('zset', 0, 100), $command->getArguments());
    }

    /**
     * @group connected
     */
    public function testReturnsElementsInScoreRange(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');

        $this->assertSame(array('a'), $redis->zrangebyscore('letters', -10, -10));
        $this->assertSame(array('c', 'd', 'e', 'f'), $redis->zrangebyscore('letters', 10, 30));
        $this->assertSame(array('d', 'e'), $redis->zrangebyscore('letters', 20, 20));
        $this->assertSame(array(), $redis->zrangebyscore('letters', 30, 0));

        $this->assertSame(array(), $redis->zrangebyscore('unknown', 0, 30));
    }

    /**
     * @group connected
     */
    public function testInfinityScoreIntervals(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');

        $this->assertSame(array('a', 'b', 'c'), $redis->zrangebyscore('letters', '-inf', 15));
        $this->assertSame(array('d', 'e', 'f'), $redis->zrangebyscore('letters', 15, '+inf'));
        $this->assertSame(array('a', 'b', 'c', 'd', 'e', 'f'), $redis->zrangebyscore('letters', '-inf', '+inf'));
    }

    /**
     * @group connected
     */
    public function testExclusiveScoreIntervals(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');

        $this->assertSame(array('c', 'd', 'e'), $redis->zrangebyscore('letters', 10, '(30'));
        $this->assertSame(array('d', 'e', 'f'), $redis->zrangebyscore('letters', '(10', 30));
        $this->assertSame(array('d', 'e'), $redis->zrangebyscore('letters', '(10', '(30'));
    }

    /**
     * @group connected
     */
    public function testRangeWithWithscoresModifier(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');
        $expected = array('c' => '10', 'd' => '20', 'e' => '20');

        $this->assertSame($expected, $redis->zrangebyscore('letters', 10, 20, 'withscores'));
        $this->assertSame($expected, $redis->zrangebyscore('letters', 10, 20, array('withscores' => true)));
    }

    /**
     * @group connected
     */
    public function testRangeWithLimitModifier(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');
        $expected = array('d', 'e');

        $this->assertSame($expected, $redis->zrangebyscore('letters', 10, 20, array('limit' => array(1, 2))));
        $this->assertSame($expected, $redis->zrangebyscore('letters', 10, 20, array('limit' => array('offset' => 1, 'count' => 2))));
    }

    /**
     * @group connected
     */
    public function testRangeWithCombinedModifiers(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');

        $options = array('limit' => array(1, 2), 'withscores' => true);
        $expected = array('d' => '20', 'e' => '20');

        $this->assertSame($expected, $redis->zrangebyscore('letters', 10, 20, $options));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->zrangebyscore('foo', 0, 10);
    }
}
