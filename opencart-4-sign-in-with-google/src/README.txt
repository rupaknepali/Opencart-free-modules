Sign in with Google for OpenCart 4 - free extension by Webocreation
https://webocreation.com/opencart-4-sign-in-with-google/

Tested on OpenCart 4.1.0.4 (PHP 8.3).
Adds a "Continue with Google" button to the login, register and checkout pages.
OpenID Connect authorization code flow with PKCE, state and nonce. No Google JavaScript is loaded.

1. Google Cloud Console > APIs & Services > OAuth consent screen: set it up (External, publish it).
2. Credentials > Create credentials > OAuth client ID > Web application.
   Authorized redirect URI: copy it from the extension settings page, e.g.
   https://www.example.com/index.php?route=extension/sociallogin/module/sociallogin.callback
3. Extensions > Installer: upload sociallogin.ocmod.zip and click Install.
   Extensions > Extensions > Modules: install Sign in with Google, Edit, paste the Client ID and
   Client secret, turn Status on and save.

Uninstalling keeps the oc_customer_social table (links between Google accounts and customers).
Free to use and modify. No attribution required.
