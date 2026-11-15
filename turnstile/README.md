# Cloudflare Turnstile

Free, privacy-friendly Cloudflare Turnstile captcha for the OpenCart forms you choose in the store captcha settings.

| | |
|---|---|
| **Platform** | OpenCart 4.0.2.x – 4.1.x (tested on 4.1.0.4, PHP 8.3) |
| **Type** | OpenCart 4 extension (`.ocmod.zip`, uses Events) |
| **Download** | [`turnstile.ocmod.zip`](turnstile.ocmod.zip) |
| **Guide & support** | [webocreation.com/opencart-4-cloudflare-turnstile-captcha/](https://webocreation.com/opencart-4-cloudflare-turnstile-captcha/) |

## Installation

1. Download [`turnstile.ocmod.zip`](turnstile.ocmod.zip).
2. In the admin go to **Extensions > Installer**, upload the zip, then click the green **Install** button.
3. Go to **Extensions > Extensions**, choose **Captchas**, install **Cloudflare Turnstile**, then click **Edit**, set Status to Enabled and save.
4. Create a widget in the Cloudflare dashboard (Turnstile > Add widget), add your hostnames, and paste the Site Key and Secret Key into the extension.
5. System > Settings > your store > Option: set **Captcha** to *Cloudflare Turnstile* and tick the pages to protect.

## Notes

- Test keys for staging: site key `1x00000000000000000000AA` with secret `1x0000000000000000000000000000000AA` always pass.

## More free extensions

- Full write-up for this extension: [https://webocreation.com/opencart-4-cloudflare-turnstile-captcha/](https://webocreation.com/opencart-4-cloudflare-turnstile-captcha/)
- All free OpenCart extensions: [https://webocreation.com/opencart/opencart-extensions-free-download/](https://webocreation.com/opencart/opencart-extensions-free-download/)
- Website: [webocreation.com](https://webocreation.com)

Free to use and modify. Issues and pull requests are welcome.
