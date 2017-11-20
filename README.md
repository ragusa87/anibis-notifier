# Anibis-notifier
Send a [Telegram](https://telegram.org/) message when a new offer for "Appartement Lausanne" is here on <http://www.anibis.ch/> or <http://www.homegate.ch/>

This project is for an internal use. Search terms are hardcoded.

## Installation
1. Install this project via `git clone`.
2. Create an environment variable for your bot API key `TELEGRAM_BOT_KEY`.
3. If you are debugging, set an environment variable `APP_DEBUG` with a value to `true`
4. Run the app with docker

## Configuration
1. Visit [Telegram Bothfather](https://telegram.me/BotFather) from Telegram app
2. Create a new Bot (/newbot) and follow instructions
3. Add your new bot API key to parameters.yml or as environment variable.
4. Enable your webhook via a command-line `php web/index.php $BOT_WEB_HOOK` where $BOT_WEB_HOOK is your absolute public website url running over HTTPS.
5. Add a cron somewhere via `crontab -e` to check update from your website 
   
```*/20 * * * * wget -O /dev/null https://$BOT_WEB_HOOK/```

# Demo
This project run on AWS at <https://anibis.while.ch> and Bot is called [@AnibisBot](https://telegram.me/AnibisBot)

# Licence
MIT by Laurent Constantin
