<div id="testimonial<?php echo $module; ?>" >
  <?php foreach ($testimonials as $testimonial) { ?>

    <article class="item with-featured-image with-subtitle post post-1063 testimonial type-testimonial status-publish has-post-thumbnail hentry">
        <div class="entry-content">
            <h3><?php echo $testimonial['title']; ?></h3>
            <p>
                <?php echo $testimonial['message']; ?>
            </p>
        </div>

			<span class="testimonial-thumbnail">
			<img width="65" height="65" src="<?php echo $testimonial['image']; ?>" class="attachment-testimonial-thumb wp-post-image" alt="jamil">		</span>

        <header class="entry-header">
            <h3 class="entry-title"><?php echo $testimonial['name']; ?></h3>
            <p class="entry-subtitle"><?php echo $testimonial['position']; ?></p>
        </header>
    </article>


  <?php } ?>
</div>
<script type="text/javascript"><!--
$('#testimonial<?php echo $module; ?>').owlCarousel({
	items: 6,
	autoPlay: 5000,
	singleItem: true,
	navigation: false,
	pagination: false,
    rtl:true,
	transitionStyle: 'fade'
});
--></script>


<style>

    .entry-content{
        margin-bottom: 10px;
        padding: 20px;
        overflow: hidden;
        background: #000;
        border: 1px solid #000;
        -webkit-border-radius: 5px;
        -moz-border-radius: 5px;
        -khtml-border-radius: 5px 5px 5px 5px;
        border-radius: 5px;
        clear: both;
        color: white;

    }
    .entry-content h3{
        color: white;
    }
    .entry-content:after {
        position: absolute;
        right: 11%;
        margin-top: 15px;
        padding-top: 30px;;
        display: block;
        width: 0;
        content: "";
        border-width: 30px 30px 0 0;
        border-style: solid;
        border-color: #000 transparent;

    }
    .owl-wrapper-outer {
        border: 0px;
        -webkit-border-radius: 0px;
        -moz-border-radius: 0px;
        -o-border-radius: 0px;
        border-radius: 0px;
        -webkit-box-shadow: 0 0px 0px rgba(0,0,0,.2);
        -moz-box-shadow: 0 0px 0px rgba(0,0,0,.2);
        -o-box-shadow: 0 0px 0px rgba(0,0,0,.2);
        box-shadow: 0 0px 0px rgba(0,0,0,.2);
    }

    .testimonial-section {
        margin-bottom: -2%;
        padding: 7% 5% 0 5%;
    }

    @media only screen and (max-width:600px) {
        #content .testimonial-section {
            padding-top: 9%;
            padding-bottom: 4%;
            margin-bottom: 0;
        }
    }

    .testimonial-section-inside {
        width: 105%;

    @media only screen and (max-width:768px) {
        width: 100%;
    }
    }

    .testimonial {
        position: relative;
        display: inline-block;
        margin-right: 4%;
        margin-bottom: 5%;
        padding: 4%;
        vertical-align: top;
        width:99%;
        border: none;
        box-shadow: none;
        border-radius: 3px;
        background: #fff;
    }

    .testimonial:last-child {
        margin-bottom: 5%;
    }

    @media only screen and (max-width:768px) {
        .testimonial {
            width: 100%;
            margin-right: 0;
            margin-bottom: 14%;
            padding: 8%;
        }

        .testimonial:last-child {
            margin-bottom: 10%;
        }
    }

    .testimonial:after {
        position: absolute;
        right: 11%;
        bottom: -30px;
        display: block;
        width: 0;
        content: "";
        border-width: 30px 30px 0 0;
        border-style: solid;
        border-color: #fff rgba(0, 0, 0, 0);
    }

    .testimonial:nth-child(even) {
        margin-right: 0;
    }

    .testimonial-title {
        font-size: 34px;
        position: relative;
        margin-bottom: 4%;
        text-align: center;
    }

    @media only screen and (max-width:768px) {
        .testimonial-title {
            margin-bottom: 6%;
        }
    }

    @media only screen and (max-width:600px) {
        .testimonial-title {
            font-size: 24px;
        }
    }

    .testimonial-title:after {
        position: absolute;
        bottom: -20px;
        display: none;
        content: " ";
        border-bottom: solid 2px #ddd;
    }

    .testimonial-thumbnail {
        display: inline-block;
        margin-left: 4%;
        float: right;
    }

    #content .testimonial-thumbnail img {
        width: 55px;
        height: 55px;
        border-radius: 100px;
    }

    .testimonial .entry-content {
        font-style: italic;
        margin-bottom: 6%;
    }

    .testimonial .entry-header {
        display: inline-block;
        position: relative;
        width: auto;
        vertical-align: top;
        padding-top: 16px;
        float: right;
    }

    .testimonial.with-subtitle .entry-header {
        padding-top: 8px;
        text-align: right;
    }

    .testimonial .entry-title {
        font-size: 18px;
        margin: 0;
    }

    .testimonial .entry-subtitle {
        font-size: 14px;
        color: #9BA6AD;
    }

    @media only screen and (max-width:600px) {
        .testimonial .entry-title {
            font-size: 16px;
        }
    }

</style>