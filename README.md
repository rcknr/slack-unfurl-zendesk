# Slack unfurl Zendesk Provider

Zendesk links unfurler for [slack-unfurl].

[slack-unfurl]: https://github.com/glensc/slack-unfurl

## Installation

1. Install [slack-unfurl]
2. Require this package:
```
composer config minimum-stability dev
composer config prefer-stable true
composer require rcknr/slack-unfurl-zendesk
```
3. Merge `env.example` from this project to `.env`
4. Register provider: in `src/Application.php` add `$this->register(new \ZendeskSlackUnfurl\ServiceProvider\ZendeskUnfurlServiceProvider());`

[slack-unfurl]: https://github.com/glensc/slack-unfurl

### Zendesk API Authentication

There are several ways to authenticate with Zendesk API two of which are supported:

1. *Username and password*. Should be enabled in Admin -> Channels -> API -> Password access.
2. *Username and API token*. Should be enabled in Admin -> Channels -> API -> Token access. Append `/token` to the username and use with any API token (should be created by an admin).