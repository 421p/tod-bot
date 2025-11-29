<?php

use NapevBot\Service\CommandHandler;
use NapevBot\Repository\TodRepositoryInterface;
use PHPUnit\Framework\TestCase;
use React\Promise\PromiseInterface;

class CommandHandlerTest extends TestCase
{
    private function makeDiscordMock()
    {
        // Create a lightweight mock of Discord\Discord acceptable by Embed constructor
        return $this->getMockBuilder(Discord\Discord::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function makeMessage($content, FakeChannel $channel)
    {
        $msg = new FakeMessage($content, $channel);
        return $msg;
    }

    public function testTodWithExplicitEpochAndUtc()
    {
        $discord = $this->makeDiscordMock();
        $repo = new InMemoryRepo();
        $handler = new CommandHandler($discord, $repo);
        $channel = new FakeChannel();
        $epoch = 1700000000;
        $msg = $this->makeMessage('.tod antharas '.$epoch.' UTC', $channel);

        $handler($msg);

        $this->assertArrayHasKey('antharas', $repo->all());
        $this->assertSame($epoch, $repo->get('antharas')['tod']);
        $this->assertSame($msg->channel_id, $repo->get('antharas')['channel']);
        $this->assertGreaterThan(0, $channel->sendCount);
        $this->assertInstanceOf(Discord\Builders\MessageBuilder::class, $channel->lastPayload);
        $this->assertGreaterThan(0, $msg->deletedCount);
    }

    public function testTodWithExplicitDateTimeAndTimezone()
    {
        $discord = $this->makeDiscordMock();
        $repo = new InMemoryRepo();
        $handler = new CommandHandler($discord, $repo);
        $channel = new FakeChannel();
        $msg = $this->makeMessage('.tod orfen 2025-11-28 14:00 UTC', $channel);

        $handler($msg);

        $expected = strtotime('2025-11-28 14:00:00 UTC');
        $this->assertArrayHasKey('orfen', $repo->all());
        $this->assertSame($expected, $repo->get('orfen')['tod']);
        $this->assertInstanceOf(Discord\Builders\MessageBuilder::class, $channel->lastPayload);
        $this->assertGreaterThan(0, $msg->deletedCount);
    }

    public function testTodInvalidTimeShowsHelpAsString()
    {
        $discord = $this->makeDiscordMock();
        $repo = new InMemoryRepo();
        $handler = new CommandHandler($discord, $repo);
        $channel = new FakeChannel();
        $msg = $this->makeMessage('.tod baium nonsense Europe/Kyiv', $channel);

        $handler($msg);

        $this->assertSame(1, $channel->sendCount);
        $this->assertIsString($channel->lastPayload);
        $this->assertStringContainsString('Не удалось распознать время', $channel->lastPayload);
        $this->assertGreaterThan(0, $msg->deletedCount);
    }

    public function testWindowNoTodSendsText()
    {
        $discord = $this->makeDiscordMock();
        $repo = new InMemoryRepo();
        $handler = new CommandHandler($discord, $repo);
        $channel = new FakeChannel();
        $msg = $this->makeMessage('.window orfen', $channel);

        $handler($msg);

        $this->assertSame(1, $channel->sendCount);
        $this->assertIsString($channel->lastPayload);
        $this->assertStringContainsString('Нету ТоДа', $channel->lastPayload);
        $this->assertGreaterThan(0, $msg->deletedCount);
    }

    public function testWindowWithTodSendsEmbedViaMessageBuilder()
    {
        $discord = $this->makeDiscordMock();
        $repo = new InMemoryRepo();
        $repo->set('zaken', [
            'tod' => 1700000000,
            'channel' => 'chanX',
            'start_reminded' => false,
            'end_reminded' => false,
        ]);
        $handler = new CommandHandler($discord, $repo);
        $channel = new FakeChannel();
        $msg = $this->makeMessage('.window zaken', $channel);

        $handler($msg);

        $this->assertSame(1, $channel->sendCount);
        $this->assertInstanceOf(Discord\Builders\MessageBuilder::class, $channel->lastPayload);
        $this->assertGreaterThan(0, $msg->deletedCount);
    }

    public function testWindowAliasW()
    {
        $discord = $this->makeDiscordMock();
        $repo = new InMemoryRepo();
        $repo->set('baium', [
            'tod' => 1700000000,
            'channel' => 'chanX',
            'start_reminded' => false,
            'end_reminded' => false,
        ]);
        $handler = new CommandHandler($discord, $repo);
        $channel = new FakeChannel();
        $msg = $this->makeMessage('.w baium', $channel);

        $handler($msg);

        $this->assertSame(1, $channel->sendCount);
        $this->assertInstanceOf(Discord\Builders\MessageBuilder::class, $channel->lastPayload);
        $this->assertGreaterThan(0, $msg->deletedCount);
    }

    public function testDelNoTod()
    {
        $discord = $this->makeDiscordMock();
        $repo = new InMemoryRepo();
        $handler = new CommandHandler($discord, $repo);
        $channel = new FakeChannel();
        $msg = $this->makeMessage('.del core', $channel);

        $handler($msg);

        $this->assertSame(1, $channel->sendCount);
        $this->assertIsString($channel->lastPayload);
        $this->assertGreaterThan(0, $msg->deletedCount);
    }

    public function testDelWithTod()
    {
        $discord = $this->makeDiscordMock();
        $repo = new InMemoryRepo();
        $repo->set('core', [
            'tod' => 1700000000,
            'channel' => 'chanX',
            'start_reminded' => false,
            'end_reminded' => false,
        ]);
        $handler = new CommandHandler($discord, $repo);
        $channel = new FakeChannel();
        $msg = $this->makeMessage('.del core', $channel);

        $handler($msg);

        $this->assertSame(1, $channel->sendCount);
        $this->assertInstanceOf(Discord\Builders\MessageBuilder::class, $channel->lastPayload);
        $this->assertNull($repo->get('core'));
        $this->assertGreaterThan(0, $msg->deletedCount);
    }

    public function testTodWithOffsetTimezone()
    {
        $discord = $this->makeDiscordMock();
        $repo = new InMemoryRepo();
        $handler = new CommandHandler($discord, $repo);
        $channel = new FakeChannel();
        $msg = $this->makeMessage('.tod core 14:00 UTC+2', $channel);

        $handler($msg);

        // We cannot rely on today’s date here; just assert that it set something and responded with embed
        $this->assertArrayHasKey('core', $repo->all());
        $this->assertIsInt($repo->get('core')['tod']);
        $this->assertInstanceOf(Discord\Builders\MessageBuilder::class, $channel->lastPayload);
        $this->assertGreaterThan(0, $msg->deletedCount);
    }

    public function testListShowsOnlyNotClosedWithRelativeTimes()
    {
        $discord = $this->makeDiscordMock();
        $repo = new InMemoryRepo();
        $handler = new CommandHandler($discord, $repo);
        $channel = new FakeChannel();

        $now = time();
        // Prepare 3 bosses:
        // 1) Not started yet: ToD 10h ago => start at +2h from now
        $repo->set('antharas', [
            'tod' => $now - 10 * 3600,
            'channel' => 'chanX',
            'start_reminded' => false,
            'end_reminded' => false,
        ]);
        // 2) In progress: ToD 15h ago => window started 3h ago, ends in 6h
        $repo->set('zaken', [
            'tod' => $now - 15 * 3600,
            'channel' => 'chanX',
            'start_reminded' => true,
            'end_reminded' => false,
        ]);
        // 3) Closed: ToD 22h ago => window ended 1h ago
        $repo->set('orfen', [
            'tod' => $now - 22 * 3600,
            'channel' => 'chanX',
            'start_reminded' => true,
            'end_reminded' => true,
        ]);

        $msg = $this->makeMessage('.list', $channel);
        $handler($msg);

        $this->assertSame(1, $channel->sendCount);
        $this->assertIsString($channel->lastPayload);
        $this->assertStringContainsString('Текущие ТоД/окна', $channel->lastPayload);
        // Should include Antharas and Zaken, not Orfen
        $this->assertStringContainsString('Antharas', $channel->lastPayload);
        $this->assertStringContainsString('Zaken', $channel->lastPayload);
        $this->assertStringNotContainsString('Orfen', $channel->lastPayload);
        // Should include relative timestamp tokens
        $this->assertStringContainsString('<t:', $channel->lastPayload);
        $this->assertStringContainsString(':R>', $channel->lastPayload);
        $this->assertGreaterThan(0, $msg->deletedCount);
    }
}

class FakeChannel
{
    public $sendCount = 0;
    public $lastPayload = null;

    public function sendMessage($payload): PromiseInterface
    {
        $this->sendCount++;
        $this->lastPayload = $payload;
        return React\Promise\resolve(true);
    }
}

class FakeMessage
{
    public $content;
    public $channel;
    public $channel_id;
    public $deletedCount = 0;

    public function __construct($content, $channel)
    {
        $this->content = $content;
        $this->channel = $channel;
        $this->channel_id = 'test-channel';
    }

    public function delete(): PromiseInterface
    {
        $this->deletedCount++;
        return React\Promise\resolve(true);
    }
}

class InMemoryRepo implements TodRepositoryInterface
{
    private $data = [];
    public function all() { return $this->data; }
    public function get($boss) { return isset($this->data[$boss]) ? $this->data[$boss] : null; }
    public function set($boss, $data) { $this->data[$boss] = $data; }
    public function delete($boss) { unset($this->data[$boss]); }
    public function save() { /* no-op */ }
}
