<?php echo $header; ?>
    <?php echo $column_left; ?>
    
    <div id="content">
        <div class="page-header">
            <div class="container-fluid">
                <h1><?php echo $error_database; ?></h1>
                <ul class="breadcrumb">
                    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                    <?php } ?>
                </ul>
            </div>
        </div>
        
        <div class="container-fluid">
            <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i>
                <?php echo $text_install_message; ?> <a href="<?php echo $install_database; ?>" class="btn btn-info"><?php echo $text_upgread; ?></a>
            </div>
        </div>
        
    </div>
    
<?php echo $footer; ?>