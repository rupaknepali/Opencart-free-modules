# OpenCart Default Events SQL

SQL file that restores OpenCart's default `oc_event` rows, for when events were deleted by a broken extension.

| | |
|---|---|
| **Platform** | OpenCart 4.x |
| **Type** | Resource |
| **Guide & support** | [webocreation.com](https://webocreation.com) |

## Notes

- **Warning:** the script runs `TRUNCATE TABLE oc_event`, which removes every event, including those added by extensions. Back up your database first and re-install extensions afterwards.
- Replace the `oc_` prefix if your store uses a different `DB_PREFIX`.

## More free extensions

- Full write-up for this extension: [https://webocreation.com](https://webocreation.com)
- All free OpenCart extensions: [https://webocreation.com/opencart/opencart-extensions-free-download/](https://webocreation.com/opencart/opencart-extensions-free-download/)
- Website: [webocreation.com](https://webocreation.com)

Free to use and modify. Issues and pull requests are welcome.
