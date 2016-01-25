# Anibis-notifier
Send a [Telegram](https://telegram.org/) message when a new offer for "Appartement Lausanne" is here on <http://www.anibis.ch/> or <http://www.homegate.ch/>

This project is for an internal use. Search terms are hardcoded.

## Installation
1. Install this project via `git clone` and configure your webserver (Access web/app.php via HTTPS)
2. run `composer install`
3. Create a cache folder: `mkdir -p var/cache && chmod -R 777 var/cache`
4. Create a config file: `cp parameters.yml.dist parameters.yml`

## Configuration
1. Visit [Telegram Bothfather](https://telegram.me/BotFather) from Telegram app
2. Create a new Bot (/newbot) and follow instructions
3. Add your new bot API key to parameters.yml.
4. Enable your webhook via a command-line `php web/bot.php URL` where URL is your absolute public website url running over HTTPS.
5. Add a cron via `crontab -e` to check update from your website 
   
```*/20 * * * * wget -O /dev/null https://URL/```

# Demo
This project run on my RaspberryPi at <https://anibis.while.ch> and Bot is called [@AnibisBot](https://telegram.me/AnibisBot)

# Licence
MIT by Laurent Constantin
