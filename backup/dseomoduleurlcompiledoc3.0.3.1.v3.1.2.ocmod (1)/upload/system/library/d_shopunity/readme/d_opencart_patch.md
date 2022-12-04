# OpenCart Patch API
Fix issues on Opencart 2.2.0.0 and below and add support for new Extension folder system.

### Why?
To reduce the compatibility issues between OpenCart version, this extension provides the missing features and methods, that are available in 2.3.0.x. They are strictly set and will not change in the future, so that you can rely on them when developing. When a new version is added, they can be extended and modified to support the new changes, but will always have the same API.


# Docs

## admin/model/extension/d_opencart_patch

### /event
Basic event methods, that are missing in 2.2.0.0 and bellow. This Model will not implement support for Events on older version. For this please install **d_event_manager**
```php
$this->load->model('extension/d_opencart_patch/event');
$event_id = $this->model_extension_d_opencart_patch_event->addEvent($code, $trigger, $action);
```


**- addEvent($code, $trigger, $action, $status = 1)**

_default opencart method that sets an event. If it is called in an old OpenCart version, which has no support for new event structure, the installDatabase() will be called and the event table will be added_


**- deleteEvent($code)**

_will delete all events with the same code name. this method is basic and is not good for removing a specific event. If you want more controll - use Event Manager_


**- installDatabase()**

_adds event table to OpenCart and also checks for missing columns and adds them too_

### /extension
OpenCart default methods for managing extension in admin
```php
$this->load->model('extension/d_opencart_patch/extension');
$extensions = $this->model_extension_d_opencart_patch_extension->getInstalled("module");
```


**- getInstalled($type)**

_default OpenCart method for returning all installed extensions by type_


**- isInstalled($code, $type = false)**

_checks if the provided extension is installed by the codename. If you want a more strict check,  supply the $type of the extension_


**- install($type, $code)**

_default opencart method for installing extensions_


**- uninstall($type, $code)**

_default opencart method for uninstalling extensions_

### /modification
OpenCarts OCmod system. Provides default modification methods as well as setModification.
```php
$this->load->model('extension/d_opencart_patch/modification');
$extensions = $this->model_extension_d_opencart_patch_modification->setModification("d_opencart_patch.xml", 1);
```


**- setModification($xml, $status = 1)**

_install a OCmod xml file on the fly. Provide a full path to the xml file beginign from root. Or you can provide the short path if your xml file is located in system/library/d_shopunity/install/_


**- refreshCache()**

_refresh the OCmod cache. This will not trigger a maintenecne mode since this could result into crashing the store or removing it from the Google index without the administrator even noticing it_


**- getModificationByCode($code)**

_returns the modification by Code. Code as we know it is avaliable starting from 2.0.1.x. In 2.0.0.0 code was XML. We suggest using getModificationByName() and specifiying the codename. And in the ocmod.xml keep the code and name identical_


**- getModificationByName($code)**

_returns the modification by Name. Beucase Name was in OpenCart since 2.x it is better to use this option and keep the code and name identical_


**- addModification($data)**

_default method for adding a OCmod modification to the database. Resolves conflict with 2.0.0.0 where code is actually xml. Use the latest $data structure. $data = array( 'code' , 'name', 'author', 'version', 'link', 'xml', 'status');_

### /setting
OpenCarts default settings methods fixed. These are one of the most often used methods and if they are changed, this will cause a great deal of updating. To avoid this, we added them here in case they ever do change.
```php
$this->load->model('extension/d_opencart_patch/setting');
$extensions = $this->model_extension_d_opencart_patch_setting->getSetting("d_opencart_patch.xml");
```


**- getSetting($code, $store_id = 0)**

_same as default method_


**- editSetting($code, $data, $store_id = 0)**

_same as default method_


**- deleteSetting($code, $store_id = 0)**

_same as default method_


**- editSettingValue($code = '', $key = '', $value = '', $store_id = 0)**

_same as default method_

### /store
Missing store methods
```php
$this->load->model('extension/d_opencart_patch/store');
$extensions = $this->model_extension_d_opencart_patch_store->getAllStores();
```


**- getAllStores()**

_returns a list of all stores, even the 0 store, which is not in the database. this method is used mostly for a multistore to optimize your template code_

### /user
Conflict fix for older versions of OpenCart when library/user did not have user_group_id
```php
$this->load->model('extension/d_opencart_patch/user');
$extensions = $this->model_extension_d_opencart_patch_user->getGroupId();
```


**- getGroupId()**

_returns getGroupId. Missing in opencart 2.0.0.0_

### /vqmod
Some handy methods for activating xml files. ALthough VQmod is oldschool it still to have for development.

```php
$this->load->model('extension/d_opencart_patch/vqmod');
$extensions = $this->model_extension_d_opencart_patch_vqmod->setModification('d_opencart_patch.xml');
```


**- setModification()**

_manage your vqmod.xml files. Activating or deactivating is really just commenting out your vqmod.xml file._


**- refreshCache()**

_refresh VQmod cache. Actually what is does is deletes the mods.cache file which vqmod uses to keep trek of chache updates. With the next call to server a new cache will be regenerated by vqmod_


## catalog/model/extension/d_opencart_patch

### /design
This model will port the latest design methods to all previouse OpenCart versions.

### /user 

