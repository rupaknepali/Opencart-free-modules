# Button Click to Checkout

Adds a product (with options and quantity) to the cart from a single link and sends the customer straight to checkout. Useful for email campaigns, landing pages and social posts.

| | |
|---|---|
| **Platform** | OpenCart 3.0.x |
| **Type** | OpenCart 3 extension / OCMOD |
| **Download** | [`link-click-to-checkout.ocmod.zip`](link-click-to-checkout.ocmod.zip) |
| **Guide & support** | [webocreation.com](https://webocreation.com) |

## Installation

1. Download [`link-click-to-checkout.ocmod.zip`](link-click-to-checkout.ocmod.zip).
2. In the admin go to **Extensions > Installer** and upload the zip.
3. Go to **Extensions > Modifications** and click **Refresh** (top right).

## Usage

```
https://yourstore.com/index.php?route=extension/module/buttonclickcheckout/add&product_id=58&options=246_71-247_72&quantity=2
```

- `product_id` – required.
- `quantity` – optional, defaults to 1.
- `options` – optional. `optionId_optionValueId` pairs separated by `-`. Example: Size (id 246) = Small (id 71) and Color (id 247) = Black (id 72) becomes `246_71-247_72`.

See [readme.txt](readme.txt) for the original notes.

## More free extensions

- Full write-up for this extension: [https://webocreation.com](https://webocreation.com)
- All free OpenCart extensions: [https://webocreation.com/opencart/opencart-extensions-free-download/](https://webocreation.com/opencart/opencart-extensions-free-download/)
- Website: [webocreation.com](https://webocreation.com)

Free to use and modify. Issues and pull requests are welcome.
