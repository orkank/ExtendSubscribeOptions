# Magento 2 Extended Subscribe Options

A Magento 2 module that extends newsletter subscription functionality with additional communication preference options (Email, Call, SMS, WhatsApp) and replaces the standard newsletter checkbox.

## Dependencies

- [Netgsm IYS Module](https://github.com/orkank/Netgsm-IYS-module)
- [Netgsm SMS Module](https://github.com/orkank/Netgsm-SMS-module)

## Features

- **Custom Email Subscription**: Replaces Magento's standard newsletter checkbox with configurable options
- **Additional Communication Channels**: Call, SMS, and WhatsApp subscription options
- **Admin Configuration**: Full admin panel configuration for all subscription options
- **Multi-language Support**: English and Turkish translations included
- **Responsive UI**: Modern checkbox design with title, subtitle, and detailed information modal
- **Newsletter Management**: Enhanced newsletter management page with save functionality

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

### Available Settings

- **Enable/Disable Options**: Toggle each communication channel (Email, Call, SMS, WhatsApp)
- **Labels**: Custom titles for each subscription option
- **Subtitles**: HTML-supported subtitle text displayed under each option
- **Descriptions**: Detailed information shown in modal popups

### UI Structure

Each subscription option displays as:
```
☐ [Checkbox] Title
   Subtitle (HTML rendered)
   [Detailed Information] (Opens modal)
```

## Usage

### Customer Registration
- Standard newsletter checkbox is hidden
- Custom subscription options are displayed
- All preferences are saved during registration

### Newsletter Management
- Enhanced newsletter management page
- Save button for updating preferences
- Modal popups for detailed information

## Multi-language Support

- **English**: `i18n/en_US.csv`
- **Turkish**: `i18n/tr_TR.csv`

## License

MIT License