## Discord ToD tracker

Overview
TodBot is a lightweight Discord bot to record boss Time of Death (ToD), show respawn windows, and post reminders when a window opens and closes. Currently supports standard Lineage 2 RB respawn time 12+9.

### Features
- Commands:
  - .tod <boss> [time] [timezone]
  - .window or .w <boss>
  - .del <boss>
  - .list or .ls or .all or .список
- Per-channel isolation (each Discord channel/server has its own ToD list and reminders)
- Automatic reminders at ToD + 12h (window start) and ToD + 21h (window end)
- User-local time display using Discord dynamic timestamps (tokens like t:UNIX:STYLE)
- Auto-deletes the invoking user message after handling (if the bot has permissions)
- Storage backends: JSON (default) or SQLite
- Translations: Russian (default) and English via environment variable
- Dockerfile and docker-compose included

### Requirements
- Native run:
  - PHP 8.4 or newer
  - Composer
  - pdo_sqlite extension if you use the SQLite backend
- Docker run:
  - Docker Engine 24 or newer

### Installation (native)
1. Clone and install dependencies
   - git clone <repo-url>
   - cd TodBot
   - composer install
2. Configure environment
   - DISCORD_TOKEN: your Discord bot token (required)
   - TOD_STORAGE: json or sqlite (default: json)
   - TOD_SQLITE: path to SQLite file when TOD_STORAGE=sqlite (default: ./data/tods.sqlite)
   - BOT_LOCALE: ru or en (default: ru)
   - TZ: UTC is recommended
3. Run
   - php bin/bot.php

### Installation (Docker)
- Build locally and run
  - docker build -t TodBot:latest .
  - docker run -d --name TodBot -e DISCORD_TOKEN=your_token -e TOD_STORAGE=sqlite -e TZ=UTC -v "$(pwd)/data:/app/data" --restart unless-stopped TodBot:latest

- Using docker-compose
  - export DISCORD_TOKEN=your_token
  - docker compose up -d

- Pulling from a container registry (if configured)
  - docker pull ghcr.io/<owner>/<repo>:latest
  - docker run -d --name TodBot -e DISCORD_TOKEN=your_token -v "$(pwd)/data:/app/data" --restart unless-stopped ghcr.io/<owner>/<repo>:latest

### Configuration
- DISCORD_TOKEN — required
- TOD_STORAGE — json (default) or sqlite
- TOD_SQLITE — path to DB file when using sqlite (default: ./data/tods.sqlite)
- BOT_LOCALE — ru (default) or en
- TZ — system timezone; the app itself uses UTC internally

### Data files
- JSON file: ./data/tods.json
- SQLite file: ./data/tods.sqlite

### Discord permissions
- Required: Read Messages/View Channel, Send Messages
- Recommended: Manage Messages (to allow the bot to delete user command messages)

### Commands and examples
Notes
- Boss names are case-insensitive.
- Displayed times are rendered by Discord in each viewer’s local timezone using dynamic timestamp tokens.
- The .tod command accepts flexible time formats and an optional timezone.

1) .tod <boss> [time] [timezone]
- Sets ToD for a boss. If time is omitted, now is used. If timezone is omitted, UTC is used.
- Examples:
  - .tod antharas
  - .tod antharas now
  - .tod antharas 1700000000 UTC (unix epoch)
  - .tod antharas 14:30 Europe/Kyiv (HH:MM and IANA timezone)
  - .tod antharas 1430 UTC+2 (HHMM and UTC offset)
  - .tod antharas 2025-11-28 14:00 UTC (full date and time)
  - .tod antharas 28.11.2025 14:00 UTC (dd.mm.yyyy HH:MM)
  - .tod antharas 28-11 14:00 UTC (dd-mm HH:MM; current year assumed)
  - .tod antharas 30m ago (relative)
  - .tod antharas 2h (relative; treated as “ago”)

### Accepted time formats
- now
- Unix timestamp (10 digits)
- Relative: examples 30m ago, 2h, -45m
- Clock: HH:MM or HHMM
- Full date-time: Y-m-d H:i, Y/m/d H:i, d.m.Y H:i, d-m-Y H:i, d/m/Y H:i
- Short date-time: d-m H:i, d.m H:i (current year assumed)

### Accepted timezones
- IANA (for example Europe/Kyiv, America/New_York)
- UTC or GMT
- Offsets like UTC+2, +2, GMT-3

2) .window <boss> (alias: .w)
- Shows last ToD and window start/end for the boss.
- Examples:
  - .window baium
  - .w zaken

3) .del <boss>
- Deletes the stored ToD.
- Example: .del orfen

4) .list (aliases: .ls, .all, .список)
- Lists bosses for the current channel whose window has not yet closed.
- If the window has not started yet, it shows “opens in …”. If it is in progress, it shows “closes in …”.
- Example: .list

### Per-channel isolation
- Each channel maintains its own ToDs and reminders. Using the bot on multiple servers/channels keeps data separate.

### Localization (i18n)
- Default locale: en
- Switch to Russian: set BOT_LOCALE=rn
- Translation files: `translations/messages.ru.php` and `translations/messages.en.php`

### Project internals
- Entry point: bin/bot.php
- Key classes:
  - src/Bot/DiscordBot.php — bootstraps Discord client and wires events
  - src/Service/CommandHandler.php — parses and responds to commands
  - src/Service/ReminderScheduler.php — periodic 60s checks to post start/end reminders
  - src/Service/TimeParser.php — parses flexible time inputs into UTC
  - src/Service/TimeFormatter.php — formats Discord timestamp tokens
  - src/Repository — JSON and SQLite backends implementing the repository interface
- Storage schema basics:
  - Primary key: boss + channel (per-channel isolation)
  - Flags: start_reminded, end_reminded

### Testing
- Run all tests: ./vendor/bin/phpunit (or composer test)
- CI: GitHub Actions runs tests on push and PR. On push, a Docker image is also built and published to GHCR if configured.

### Troubleshooting
- DISCORD_TOKEN is not set — export DISCORD_TOKEN=your_token
- Bot does not delete user messages — grant Manage Messages permission
- SQLite errors (native) — enable pdo_sqlite or use the Docker image
- No reminders — the scheduler ticks every 60 seconds; make sure the bot can send messages in the channel where .tod was used
- Time parsing failed — the bot will respond with examples; try another format or specify a timezone
