

http://yourwebsite.com/index.php?route=extension/module/buttonclickcheckout/add&product_id=58&options=246_71-247_72&quantity=2


Here,
product_id=58
quantity=2
options=246_71-247_72

If you don't have options then you can send only product_id.

If you want to add only one quantity then no need to pass but if you want to add more quantity when clicked then you need to send quantity value as well.

If you have options then it is quite tricky.
If your option is Size (id=246) having option value Small (id=71)
and another option is Color (id=247) having option value black.
Need to take care of dashes and underscores, dashes separate between option and underscore separate between option and option value.

