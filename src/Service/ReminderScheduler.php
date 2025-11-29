<?php

namespace NapevBot\Service;

use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use NapevBot\Repository\TodRepositoryInterface;

class ReminderScheduler
{
    private Discord $discord;
    private TodRepositoryInterface $repo;

    public function __construct(Discord $discord, TodRepositoryInterface $repo)
    {
        $this->discord = $discord;
        $this->repo = $repo;
    }

    public function start(): void
    {
        $discord = $this->discord;
        $repo = $this->repo;

        $discord->loop->addPeriodicTimer(60, function () use ($discord, $repo) {
            $now = time();
            $tods = $repo->all(); // [channelId => [boss => info]]

            foreach ($tods as $channelId => $byBoss) {
                if (!$channelId) { continue; }

                $channel = $discord->getChannel($channelId);
                if (!$channel) { continue; }

                foreach ($byBoss as $boss => $info) {
                    $tod = $info['tod'] ?? 0;
                    $startReminded = !empty($info['start_reminded']);
                    $endReminded = !empty($info['end_reminded']);

                    $start = $tod + 12 * 3600;
                    $end = $tod + 21 * 3600;

                    if (!$startReminded && $now >= $start) {
                        $embed = new Embed($discord);
                        $embed->setTitle(I18n::t('reminder.start.title', ['%boss%' => ucfirst($boss)]))
                            ->setColor(0x00cc99)
                            ->addFieldValues(I18n::t('reminder.start.field'), TimeFormatter::discord($start), true);
                        $channel->sendMessage(MessageBuilder::new()->addEmbed($embed));
                        $info['start_reminded'] = true;
                    }

                    if (!$endReminded && $now >= $end) {
                        $embed = new Embed($discord);
                        $embed->setTitle(I18n::t('reminder.end.title', ['%boss%' => ucfirst($boss)]))
                            ->setColor(0xFF6600)
                            ->addFieldValues(I18n::t('reminder.end.field'), TimeFormatter::discord($end), true);
                        $channel->sendMessage(MessageBuilder::new()->addEmbed($embed));
                        $info['end_reminded'] = true;
                    }

                    // Persist updates if changed
                    $repo->set($boss, $channelId, $info);
                }
            }

            $repo->save();
        });
    }
}
