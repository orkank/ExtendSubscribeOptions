# Magento 2 Extended Subscribe Options

A Magento 2 module that adds additional communication preference options (Call, SMS, WhatsApp) to the newsletter subscription functionality.

## Dependencies

- [Netgsm IYS Module](https://github.com/orkank/Netgsm-IYS-module)
- [Netgsm SMS Module](https://github.com/orkank/Netgsm-SMS-module)

## Features

- Additional subscription options in registration and newsletter management pages
- Supports call, SMS, and WhatsApp communication channels
- Admin configuration for each channel

## Installation

1. Create `app/code/IDangerous/ExtendSubscribeOptions` directory
2. Copy module files
3. Run:

```bash
php bin/magento module:enable IDangerous_ExtendSubscribeOptions
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:clean
```

## Configuration

Admin > Stores > Configuration > IDangerous > Subscription Options

## License

MIT License