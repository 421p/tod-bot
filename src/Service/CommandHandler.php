<?php

namespace NapevBot\Service;

use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use NapevBot\Repository\TodRepositoryInterface;

class CommandHandler
{
    private Discord $discord;
    private TodRepositoryInterface $repo;

    public function __construct(Discord $discord, TodRepositoryInterface $repo)
    {
        $this->discord = $discord;
        $this->repo = $repo;
    }

    public function __invoke($message): void
    {
        $content = trim($message->content);
        $parts = explode(' ', $content);
        $cmd = strtolower($parts[0]);

        if (in_array($cmd, ['.tod', '.тод']) && isset($parts[1])) {
            $boss = strtolower($parts[1]);
            $args = array_slice($parts, 2);
            $timeArg = null;
            $tzArg = null;
            if (!empty($args)) {
                // If there are 2+ args, try to detect if the last one is a timezone, then join the rest as time
                if (count($args) >= 2) {
                    $maybeTz = $args[count($args) - 1];
                    if ($this->looksLikeTimezone($maybeTz)) {
                        $tzArg = $maybeTz;
                        $timeArg = trim(implode(' ', array_slice($args, 0, -1)));
                    } else {
                        $timeArg = trim(implode(' ', $args));
                    }
                } else {
                    $timeArg = $args[0];
                }
            }
            $this->handleTod($message, $boss, $timeArg, $tzArg);
            return;
        }

        if (in_array($cmd, ['.window', '.w', '.вікно', '.окно']) && isset($parts[1])) {
            $this->handleWindow($message, strtolower($parts[1]));
            return;
        }

        if (in_array($cmd, ['.del', '.дел']) && isset($parts[1])) {
            $this->handleDelete($message, strtolower($parts[1]));
            return;
        }

        if (in_array($cmd, ['.list', '.ls', '.all', '.список'])) {
            $this->handleList($message);
            return;
        }
    }

    private function looksLikeTimezone($s): bool
    {
        $s = trim($s);
        if ($s === '') return false;
        $u = strtoupper($s);
        if ($u === 'UTC' || $u === 'GMT') return true;
        if (preg_match('/^(UTC|GMT)?\s*[+-]\s*\d{1,2}$/', $u)) return true;
        // IANA tz contains a slash usually, like Europe/Kyiv or America/New_York
        if (str_contains($s, '/')) return true;
        return false;
    }

    private function handleTod($message, $boss, $timeArg = null, $tzArg = null): void
    {
        $parsed = TimeParser::parse($timeArg, $tzArg, time());
        $now = $parsed['ts'];
        $tzUsed = $parsed['tz'];

        if ($now === null) {
            $help = "Не удалось распознать время. Примеры:\n"
                . ".tod antharas 14:30 Europe/Kyiv\n"
                . ".tod baium 1430 UTC+2\n"
                . ".tod zaken 2025-11-28 14:00 UTC\n"
                . ".tod orfen now\n"
                . ".tod core 30m ago";
            $message->channel->sendMessage($help)
                ->then(function () use ($message) { $message->delete(); }, function () use ($message) { $message->delete(); });
            return;
        }

        $data = [
            'tod' => $now,
            'channel' => $message->channel_id,
            'start_reminded' => false,
            'end_reminded' => false,
        ];
        $this->repo->set($boss, $data);
        $this->repo->save();

        $start = $now + 12 * 3600;
        $end = $now + 21 * 3600;

        $embed = new Embed($this->discord);
        $embed->setTitle('💀 ' . ucfirst($boss) . ' был отпизжен.')
            ->setColor(0x3498db)
            ->addFieldValues('Время смерти', TimeFormatter::discord($now), false)
            ->addFieldValues('Начало окна', TimeFormatter::discord($start), true)
            ->addFieldValues('Конец окна', TimeFormatter::discord($end), true);

        // Use MessageBuilder to send embeds (discord-php >=10)
        // Delete user's command message after responding (if bot has permission)
        $message->channel->sendMessage(MessageBuilder::new()->addEmbed($embed))
            ->then(function () use ($message) {
                $message->delete();
            }, function () use ($message) {
                $message->delete();
            });
    }

    private function handleWindow($message, $boss): void
    {
        $info = $this->repo->get($boss);
        if (!$info) {
            $message->channel->sendMessage("Нету ТоДа для **$boss**.")
                ->then(function () use ($message) {
                    $message->delete();
                }, function () use ($message) {
                    $message->delete();
                });
            return;
        }

        $tod = $info['tod'];
        $start = $tod + 12 * 3600;
        $end = $tod + 21 * 3600;

        $embed = new Embed($this->discord);
        $embed->setTitle('📅 Окно респа:' .  ucfirst($boss))
            ->setColor(0x2ecc71)
            ->addFieldValues('Последний ТоД', TimeFormatter::discord($tod), false)
            ->addFieldValues('Начало окна', TimeFormatter::discord($start), true)
            ->addFieldValues('Конец окна', TimeFormatter::discord($end), true);

        // Use MessageBuilder to send embeds (discord-php >=10)
        $message->channel->sendMessage(MessageBuilder::new()->addEmbed($embed))
            ->then(function () use ($message) {
                $message->delete();
            }, function () use ($message) {
                $message->delete();
            });
    }

    private function handleDelete($message, $boss): void
    {
        $info = $this->repo->get($boss);
        if (!$info) {
            $message->channel->sendMessage("Нету ТоДа для **$boss**.")
                ->then(function () use ($message) {
                    $message->delete();
                }, function () use ($message) {
                    $message->delete();
                });
            return;
        }

        $this->repo->delete($boss);
        $this->repo->save();

        $embed = new Embed($this->discord);
        $embed->setTitle('❌ Удалили ТоД: '.ucfirst($boss))
            ->setColor(0xFF3333);

        // Use MessageBuilder to send embeds (discord-php >=10)
        $message->channel->sendMessage(MessageBuilder::new()->addEmbed($embed))
            ->then(function () use ($message) {
                $message->delete();
            }, function () use ($message) {
                $message->delete();
            });
    }

    private function handleList($message): void
    {
        $all = $this->repo->all();
        $now = time();
        $lines = [];
        foreach ($all as $boss => $info) {
            if (!isset($info['tod'])) continue;
            $tod = (int) $info['tod'];
            $start = $tod + 12 * 3600;
            $end = $tod + 21 * 3600;
            if ($now >= $end) {
                // window closed — skip
                continue;
            }
            $bossName = ucfirst($boss);
            if ($now < $start) {
                $lines[] = "• $bossName — окно открывается: " . TimeFormatter::discord($start, 'R');
            } else {
                $lines[] = "• $bossName — окно закрывается: " . TimeFormatter::discord($end, 'R');
            }
        }

        if (empty($lines)) {
            $text = "Нет доступных боссов.";
        } else {
            $text = "Текущие ТоДы:\n" . implode("\n", $lines);
        }

        $message->channel->sendMessage($text)
            ->then(function () use ($message) {
                $message->delete();
            }, function () use ($message) {
                $message->delete();
            });
    }
}
