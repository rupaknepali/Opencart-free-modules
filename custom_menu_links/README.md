# Custom Menu Links for OpenCart 4

## Overview

This extension allows you to easily add multiple custom links to the main navigation menu of your OpenCart 4 store. Through a simple interface in the admin panel, you can add, remove, and sort an unlimited number of menu items, giving you full control over your store's navigation.

---

## Features

- **Unlimited Menu Items:** Add as many custom links as you need.
- **Dynamic Management:** Add and remove menu items without any coding.
- **Sortable Links:** Control the order in which your links appear using a sort order field.
- **Admin Control:** Enable or disable the entire set of custom links with a single click.
- **Clean Installation:** Uses the OpenCart event system to modify the menu and cleans up after itself upon uninstallation.
- **Standard Module:** Follows OpenCart 4 best practices for module development.

---

## Installation

1.  **Prepare the Extension:** Compress the `custom_menu_links` directory into a zip file named `custom_menu_links.ocmod.zip`.
2.  **Upload:**
    *   Navigate to your OpenCart Admin Panel.
    *   Go to `Extensions > Installer`.
    *   Click the `Upload` button and select the `custom_menu_links.ocmod.zip` file.
3.  **Install the Module:**
    *   After the upload is complete, go to `Extensions > Extensions`.
    *   From the dropdown filter, select `Modules`.
    *   Find **Custom Menu Links** in the list and click the green **Install** button (plus icon).
4.  **Configure and Enable:**
    *   Once installed, click the blue **Edit** button (pencil icon) next to the module.
    *   Set the `Status` to **Enabled**.
    *   Click the **Add Menu Item** button to add your first link. Fill in the `Title`, `Link`, and `Sort Order`.
    *   Add as many items as you need.
    *   Click the **Save** button in the top right.

---

## How It Works

### Backend (Admin)

The admin controller (`admin/controller/module/custom_menu_links.php`) manages the settings for the module. It saves the status (enabled/disabled) and a list of all menu items (title, link, sort order) into the `setting` table in the database.

The view file (`admin/view/template/module/custom_menu_links.twig`) provides a user-friendly table where you can dynamically add or remove rows for each menu item using JavaScript.

### Frontend (Catalog)

The catalog controller (`catalog/controller/module/custom_menu_links.php`) contains a method that listens for the `catalog/view/common/menu/before` event. When this event is triggered, the method checks if the module is enabled, retrieves the list of saved menu items, sorts them by the specified sort order, and injects them into the data array that is used to render the main menu.

---

**Compatibility:** Developed and tested for OpenCart 4.x.
