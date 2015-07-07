<!DOCTYPE html>
<html>
<head>
    <title><?php echo $this->lang->line('page_not_found') . ' - ' . $company_name; ?></title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    
    <?php
        // ------------------------------------------------------------
        // INCLUDE CSS FILES
        // ------------------------------------------------------------ ?>
    <link 
        rel="stylesheet" 
        type="text/css" 
        href="<?php echo $this->config->item('base_url'); ?>/assets/ext/bootstrap/css/bootstrap.min.css">
    
    <?php
        // ------------------------------------------------------------
        // SET PAGE FAVICON
        // ------------------------------------------------------------ ?>
    <link 
        rel="icon" 
        type="image/x-icon" 
        href="<?php echo $this->config->item('base_url'); ?>/assets/img/favicon.ico">
    
    <?php
        // ------------------------------------------------------------
        // CUSTOM PAGE JS
        // ------------------------------------------------------------ ?>
    <script type="text/javascript">
        var EALang = <?php echo json_encode($this->lang->language); ?>;
    </script>

    <?php
        // ------------------------------------------------------------
        // INCLUDE JS FILES
        // ------------------------------------------------------------ ?>
    <script 
        type="text/javascript" 
        src="<?php echo $this->config->item('base_url'); ?>/assets/ext/jquery/jquery.min.js"></script>
    <script 
        type="text/javascript" 
        src="<?php echo $this->config->item('base_url'); ?>/assets/ext/bootstrap/js/bootstrap.min.js"></script>
    <script 
        type="text/javascript" 
        src="<?php echo $this->config->item('base_url'); ?>/assets/ext/datejs/date.js"></script>
    <script 
        type="text/javascript" 
        src="<?php echo $this->config->item('base_url'); ?>/assets/js/general_functions.js"></script>

    <?php
        // ------------------------------------------------------------
        // CUSTOM PAGE CSS
        // ------------------------------------------------------------ ?>
    <style>
        body {
            background-color: #CAEDF3;
        }
        
        #message-frame {
            width: 630px;
            margin: 150px auto 0 auto;
            background: #FFF;
            border: 1px solid #DDDADA;
            padding: 70px;
        }
        
        .btn { 
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div id="message-frame" class="frame-container">
        <h3><?php echo $this->lang->line('page_not_found') 
                . ' - ' . $this->lang->line('error') . ' 404' ?></h3>
        <p>
            <?php echo $this->lang->line('page_not_found_message'); ?>
        </p>
        
        <br>
        
        <a href="<?php echo $this->config->item('base_url'); ?>" class="btn btn-primary btn-large">
            <i class="icon-calendar icon-white"></i>
            <?php echo $this->lang->line('book_appointment_title'); ?>
        </a>
        
        <a href="<?php echo $this->config->item('base_url'); ?>/index.php/backend" class="btn btn-danger btn-large">
            <i class="icon-wrench icon-white"></i>
            <?php echo $this->lang->line('backend_section'); ?>
        </a>
        
    </div>
</body>
</html>