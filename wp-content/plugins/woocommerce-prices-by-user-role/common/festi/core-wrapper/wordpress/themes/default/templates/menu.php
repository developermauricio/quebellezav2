<nav class="navbar navbar-default" role="navigation">
    <div class="collapse navbar-collapse" id="<?php echo $name; ?>">
        <ul class="nav navbar-nav">
            <?php
            foreach ($items as $first) { // [1]
            ?>
                <li<?php if (!empty($first['items'])) { echo ' class="dropdown"'; } ?>>
                    <a<?php if (!empty($first['items'])) { echo ' class="dropdown-toggle" data-toggle="dropdown"'; } ?> <?php echo (!empty($first['href']) ? 'href="'.$first['href'].'"' : 'href="javascript:void(0)"')?>><?php echo $first['caption']?>
                      <?php if (!empty($first['items'])) { echo ' <b class="caret"></b>'; } ?>
                    </a>
                <?php
                if (!empty($first['items'])) { // [1]
                ?>
                    <ul class="dropdown-menu">
                    <?php
                    foreach ($first['items'] as $second) { // [2]
                    ?>
                        <li<?php if (!empty($second['items'])) { echo ' class="dropdown-submenu"'; } ?>>
                            <a <?php echo (!empty($second['href']) ? 'href="'.$second['href'].'"' : 'href="javascript:void(0)"')?>><?php echo $second['caption']?>
                            </a>
                            <?php
                            if (!empty($second['items'])) { // [2]
                            ?>
                                <ul class="dropdown-menu">
                                    <?php
                                    foreach ($second['items'] as $last) { // [3]
                                    ?>
                                    <li><a <?php echo (!empty($last['href']) ? 'href="'.$last['href'].'"' : 'href="javascript:void(0)"')?>><?php echo $last['caption']?></a></li>
                                    <?php
                                    } // end foreach [3]
                                    ?>
                                </ul>
                            <?php
                            } // end if [2]
                            ?>
                        </li>
                    <?php
                    } // end foreach [2]
                    ?>
                    </ul>
                <?php
                } // end if [1]
                ?>
                </li>
            <?php
            } // end foreach [1]
            ?>
    </ul>
  </div>
</nav>











