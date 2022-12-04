<?php

/* install/step_4.twig */
class __TwigTemplate_54cf83171f6e0332d182958be9056b7624ef8d940a68ab9233a69d93cab7642b extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 1
        echo (isset($context["header"]) ? $context["header"] : null);
        echo "
<div class=\"container\">
  <header>
    <div class=\"row\">
      <div class=\"col-sm-6\">
        <h1 class=\"pull-left\">4<small>/4</small></h1>
        <h3>";
        // line 7
        echo (isset($context["heading_title"]) ? $context["heading_title"] : null);
        echo "<br>
          <small>";
        // line 8
        echo (isset($context["text_step_4"]) ? $context["text_step_4"] : null);
        echo "</small></h3>
      </div>
      <div class=\"col-sm-6\">
        <div id=\"logo\" class=\"pull-right hidden-xs\"><img src=\"view/image/logo.png\" alt=\"OpenCart\" title=\"OpenCart\" /></div>
      </div>
    </div>
  </header>
  ";
        // line 15
        if ((isset($context["success"]) ? $context["success"] : null)) {
            // line 16
            echo "  <div class=\"alert alert-success alert-dismissible\">";
            echo (isset($context["success"]) ? $context["success"] : null);
            echo "</div>
  ";
        }
        // line 18
        echo "  <div class=\"alert alert-danger alert-dismissible\"><i class=\"fa fa-exclamation-circle\"></i> ";
        echo (isset($context["error_warning"]) ? $context["error_warning"] : null);
        echo "
    <button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>
  </div>
  <div class=\"visit\">
    <div class=\"row\">
      <div class=\"col-sm-5 col-sm-offset-1 text-center\">
        <p><i class=\"fa fa-shopping-cart fa-5x\"></i></p>
        <a href=\"../\" class=\"btn btn-secondary\">";
        // line 25
        echo (isset($context["text_catalog"]) ? $context["text_catalog"] : null);
        echo "</a></div>
      <div class=\"col-sm-5 text-center\">
        <p><i class=\"fa fa-cog fa-5x white\"></i></p>
        <a href=\"../admin/\" class=\"btn btn-secondary\">";
        // line 28
        echo (isset($context["text_admin"]) ? $context["text_admin"] : null);
        echo "</a></div>
    </div>
  </div>
  <div class=\"modules\">
    <div class=\"row\">
      <div class=\"col-sm-12 text-center\"><a href=\"https://www.opencart.com/index.php?route=extension/extension&utm_source=opencart_install&utm_medium=store_link&utm_campaign=opencart_install\" target=\"_BLANK\" class=\"btn btn-default\">";
        // line 33
        echo (isset($context["text_extension"]) ? $context["text_extension"] : null);
        echo "</a></div>
    </div>
  </div>
  <div class=\"mailing\">
    <div class=\"row\">
      <div class=\"col-sm-12\"><i class=\"fa fa-envelope-o fa-5x\"></i>
        <h3>";
        // line 39
        echo (isset($context["text_mail"]) ? $context["text_mail"] : null);
        echo "<br>
          <small>";
        // line 40
        echo (isset($context["text_mail_description"]) ? $context["text_mail_description"] : null);
        echo "</small></h3>
        <a href=\"http://newsletter.opencart.com/h/r/B660EBBE4980C85C\" target=\"_BLANK\" class=\"btn btn-secondary\">";
        // line 41
        echo (isset($context["button_mail"]) ? $context["button_mail"] : null);
        echo "</a></div>
    </div>
  </div>
  <div class=\"core-modules\">
    <div class=\"row\">
      <div class=\"col-sm-6 text-center\">
        <img src=\"view/image/openbay_pro.gif\" />
        <p>";
        // line 48
        echo (isset($context["text_openbay"]) ? $context["text_openbay"] : null);
        echo "</p>
        <a class=\"btn btn-primary\" href=\"";
        // line 49
        echo (isset($context["openbay"]) ? $context["openbay"] : null);
        echo "\">";
        echo (isset($context["button_setup"]) ? $context["button_setup"] : null);
        echo "</a></div>
      <div class=\"col-sm-6 text-center\">
        <img src=\"view/image/amazon-pay.png\" style=\"margin-top:25px;\" />
        <p style=\"margin-top:0px;\">";
        // line 52
        echo (isset($context["text_amazon_pay"]) ? $context["text_amazon_pay"] : null);
        echo "</p>
        <a class=\"btn btn-primary\" href=\"https://pay.amazon.com/uk/signup?ld=SPEXUKAPA-opct-AGY-Ref&ref=ag_uk_ap_np_sp_opct_xx_ay_xx_xxx\" target=\"_blank\">";
        // line 53
        echo (isset($context["button_setup"]) ? $context["button_setup"] : null);
        echo "</a></div>
    </div>
  </div>
  <div class=\"support text-center\">
    <div class=\"row\">
      <div class=\"col-sm-4\"><a href=\"https://www.facebook.com/pages/OpenCart/477182382328323\" class=\"icon transition\"><i class=\"fa fa-facebook fa-4x\"></i></a>
        <h3>";
        // line 59
        echo (isset($context["text_facebook"]) ? $context["text_facebook"] : null);
        echo "</h3>
        <p>";
        // line 60
        echo (isset($context["text_facebook_description"]) ? $context["text_facebook_description"] : null);
        echo "</p>
        <a href=\"https://www.facebook.com/pages/OpenCart/477182382328323\">";
        // line 61
        echo (isset($context["text_facebook_visit"]) ? $context["text_facebook_visit"] : null);
        echo "</a></div>
      <div class=\"col-sm-4\"><a href=\"https://forum.opencart.com/?utm_source=opencart_install&utm_medium=forum_link&utm_campaign=opencart_install\" class=\"icon transition\"><i class=\"fa fa-comments fa-4x\"></i></a>
        <h3>";
        // line 63
        echo (isset($context["text_forum"]) ? $context["text_forum"] : null);
        echo "</h3>
        <p>";
        // line 64
        echo (isset($context["text_forum_description"]) ? $context["text_forum_description"] : null);
        echo "</p>
        <a href=\"https://forum.opencart.com/?utm_source=opencart_install&utm_medium=forum_link&utm_campaign=opencart_install\">";
        // line 65
        echo (isset($context["text_forum_visit"]) ? $context["text_forum_visit"] : null);
        echo "</a></div>
      <div class=\"col-sm-4\"><a href=\"https://www.opencart.com/index.php?route=partner/partner&utm_source=opencart_install&utm_medium=partner_link&utm_campaign=opencart_install\" class=\"icon transition\"><i class=\"fa fa-user fa-4x\"></i></a>
        <h3>";
        // line 67
        echo (isset($context["text_commercial"]) ? $context["text_commercial"] : null);
        echo "</h3>
        <p>";
        // line 68
        echo (isset($context["text_commercial_description"]) ? $context["text_commercial_description"] : null);
        echo "</p>
        <a href=\"https://www.opencart.com/index.php?route=partner/partner&utm_source=opencart_install&utm_medium=partner_link&utm_campaign=opencart_install\" target=\"_BLANK\">";
        // line 69
        echo (isset($context["text_commercial_visit"]) ? $context["text_commercial_visit"] : null);
        echo "</a></div>
    </div>
  </div>
</div>
";
        // line 73
        echo (isset($context["footer"]) ? $context["footer"] : null);
        echo "
";
    }

    public function getTemplateName()
    {
        return "install/step_4.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  168 => 73,  161 => 69,  157 => 68,  153 => 67,  148 => 65,  144 => 64,  140 => 63,  135 => 61,  131 => 60,  127 => 59,  118 => 53,  114 => 52,  106 => 49,  102 => 48,  92 => 41,  88 => 40,  84 => 39,  75 => 33,  67 => 28,  61 => 25,  50 => 18,  44 => 16,  42 => 15,  32 => 8,  28 => 7,  19 => 1,);
    }
}
/* {{ header }}*/
/* <div class="container">*/
/*   <header>*/
/*     <div class="row">*/
/*       <div class="col-sm-6">*/
/*         <h1 class="pull-left">4<small>/4</small></h1>*/
/*         <h3>{{ heading_title }}<br>*/
/*           <small>{{ text_step_4 }}</small></h3>*/
/*       </div>*/
/*       <div class="col-sm-6">*/
/*         <div id="logo" class="pull-right hidden-xs"><img src="view/image/logo.png" alt="OpenCart" title="OpenCart" /></div>*/
/*       </div>*/
/*     </div>*/
/*   </header>*/
/*   {% if success %}*/
/*   <div class="alert alert-success alert-dismissible">{{ success }}</div>*/
/*   {% endif %}*/
/*   <div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> {{ error_warning }}*/
/*     <button type="button" class="close" data-dismiss="alert">&times;</button>*/
/*   </div>*/
/*   <div class="visit">*/
/*     <div class="row">*/
/*       <div class="col-sm-5 col-sm-offset-1 text-center">*/
/*         <p><i class="fa fa-shopping-cart fa-5x"></i></p>*/
/*         <a href="../" class="btn btn-secondary">{{ text_catalog }}</a></div>*/
/*       <div class="col-sm-5 text-center">*/
/*         <p><i class="fa fa-cog fa-5x white"></i></p>*/
/*         <a href="../admin/" class="btn btn-secondary">{{ text_admin }}</a></div>*/
/*     </div>*/
/*   </div>*/
/*   <div class="modules">*/
/*     <div class="row">*/
/*       <div class="col-sm-12 text-center"><a href="https://www.opencart.com/index.php?route=extension/extension&utm_source=opencart_install&utm_medium=store_link&utm_campaign=opencart_install" target="_BLANK" class="btn btn-default">{{ text_extension }}</a></div>*/
/*     </div>*/
/*   </div>*/
/*   <div class="mailing">*/
/*     <div class="row">*/
/*       <div class="col-sm-12"><i class="fa fa-envelope-o fa-5x"></i>*/
/*         <h3>{{ text_mail }}<br>*/
/*           <small>{{ text_mail_description }}</small></h3>*/
/*         <a href="http://newsletter.opencart.com/h/r/B660EBBE4980C85C" target="_BLANK" class="btn btn-secondary">{{ button_mail }}</a></div>*/
/*     </div>*/
/*   </div>*/
/*   <div class="core-modules">*/
/*     <div class="row">*/
/*       <div class="col-sm-6 text-center">*/
/*         <img src="view/image/openbay_pro.gif" />*/
/*         <p>{{ text_openbay }}</p>*/
/*         <a class="btn btn-primary" href="{{ openbay }}">{{ button_setup }}</a></div>*/
/*       <div class="col-sm-6 text-center">*/
/*         <img src="view/image/amazon-pay.png" style="margin-top:25px;" />*/
/*         <p style="margin-top:0px;">{{ text_amazon_pay }}</p>*/
/*         <a class="btn btn-primary" href="https://pay.amazon.com/uk/signup?ld=SPEXUKAPA-opct-AGY-Ref&ref=ag_uk_ap_np_sp_opct_xx_ay_xx_xxx" target="_blank">{{ button_setup }}</a></div>*/
/*     </div>*/
/*   </div>*/
/*   <div class="support text-center">*/
/*     <div class="row">*/
/*       <div class="col-sm-4"><a href="https://www.facebook.com/pages/OpenCart/477182382328323" class="icon transition"><i class="fa fa-facebook fa-4x"></i></a>*/
/*         <h3>{{ text_facebook }}</h3>*/
/*         <p>{{ text_facebook_description }}</p>*/
/*         <a href="https://www.facebook.com/pages/OpenCart/477182382328323">{{ text_facebook_visit }}</a></div>*/
/*       <div class="col-sm-4"><a href="https://forum.opencart.com/?utm_source=opencart_install&utm_medium=forum_link&utm_campaign=opencart_install" class="icon transition"><i class="fa fa-comments fa-4x"></i></a>*/
/*         <h3>{{ text_forum }}</h3>*/
/*         <p>{{ text_forum_description }}</p>*/
/*         <a href="https://forum.opencart.com/?utm_source=opencart_install&utm_medium=forum_link&utm_campaign=opencart_install">{{ text_forum_visit }}</a></div>*/
/*       <div class="col-sm-4"><a href="https://www.opencart.com/index.php?route=partner/partner&utm_source=opencart_install&utm_medium=partner_link&utm_campaign=opencart_install" class="icon transition"><i class="fa fa-user fa-4x"></i></a>*/
/*         <h3>{{ text_commercial }}</h3>*/
/*         <p>{{ text_commercial_description }}</p>*/
/*         <a href="https://www.opencart.com/index.php?route=partner/partner&utm_source=opencart_install&utm_medium=partner_link&utm_campaign=opencart_install" target="_BLANK">{{ text_commercial_visit }}</a></div>*/
/*     </div>*/
/*   </div>*/
/* </div>*/
/* {{ footer }}*/
/* */
