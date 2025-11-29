<?php

namespace NapevBot\Service;

use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use NapevBot\Repository\TodRepositoryInterface;

class ReminderScheduler
{
    private $discord;
    private $repo;

    public function __construct(Discord $discord, TodRepositoryInterface $repo)
    {
        $this->discord = $discord;
        $this->repo = $repo;
    }

    public function start()
    {
        $discord = $this->discord;
        $repo = $this->repo;

        $discord->loop->addPeriodicTimer(60, function () use ($discord, $repo) {
            $now = time();
            $tods = $repo->all();

            foreach ($tods as $boss => $info) {
                $tod = $info['tod'] ?? 0;
                $channelId = $info['channel'] ?? null;
                $startReminded = !empty($info['start_reminded']);
                $endReminded = !empty($info['end_reminded']);

                if (!$channelId) {
                    continue;
                }

                $start = $tod + 12 * 3600;
                $end = $tod + 21 * 3600;

                $channel = $discord->getChannel($channelId);
                if (!$channel) {
                    continue;
                }

                if (!$startReminded && $now >= $start) {
                    $embed = new Embed($discord);
                    $embed->setTitle("⏰ Окно респа открылось: ".ucfirst($boss))
                        ->setColor(0x00cc99)
                        ->addFieldValues("Начало окна:", TimeFormatter::discord($start), true);
                    // Use MessageBuilder to send embeds (discord-php >=10)
                    $channel->sendMessage(MessageBuilder::new()->addEmbed($embed));
                    $info['start_reminded'] = true;
                }

                if (!$endReminded && $now >= $end) {
                    $embed = new Embed($discord);
                    $embed->setTitle("⚠️ Окно респа закрылось: ".ucfirst($boss))
                        ->setColor(0xFF6600)
                        ->addFieldValues("Конец окна: ", TimeFormatter::discord($end), true);
                    // Use MessageBuilder to send embeds (discord-php >=10)
                    $channel->sendMessage(MessageBuilder::new()->addEmbed($embed));
                    $info['end_reminded'] = true;
                }

                // Persist updates if changed
                $repo->set($boss, $info);
            }

            $repo->save();
        });
    }
}
