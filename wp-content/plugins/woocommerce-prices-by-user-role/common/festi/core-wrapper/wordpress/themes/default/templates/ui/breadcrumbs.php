 <ol class="breadcrumb">
            <?php
            $chunks = array();
            if (isset($items)) {
                foreach ($items as $caption => $url) {
                    if (!$url) {
                        $chunks[] = '<li class="active"><span class="e-breadcrumb">'.$caption.'</span></li>';
                    } else {
                        $chunks[] = '<li><a href="'.$url.'" class="e-breadcrumb">'.$caption.'</a></li>';
                    }
                }
            }

            echo join('', $chunks);
            ?>
</ol>    
        