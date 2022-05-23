<ul class="pagination e-db-pager">
<?php
$totalPages = ceil($totalItems / $perPage);

$controller = Controller::getInstance();


$disabled = ' disabled';
$url = 'javascript:void(0)';
if ($currentPage != 1) {
    $disabled = '';
    $params = array(
        Store::PAGE_INDEX_KEY_IN_REQUEST => 1
    );
    $url = $controller->getUrl($baseUrl, $params);
}

echo '<li class="first'.$disabled.'"><a href="'.$url.'">'.__('First').'</a></li>';

//
$disabled = ' disabled';
$url = 'javascript:void(0)';
if ($currentPage > 1) {
    $disabled = '';
    $params = array(
        Store::PAGE_INDEX_KEY_IN_REQUEST => $currentPage - 1
    );
    $url = $controller->getUrl($baseUrl, $params);
}

echo '<li class="prev'.$disabled.'"><a href="'.$url.'">'.__('Previous').'</a></li>';

//
$startIndex = ($currentPage == 1) ? 0 : $currentPage - 2;
$maxPages = ($currentPage < $totalPages) ? $startIndex + 5 : $totalPages;
if ($maxPages > $totalPages) {
    $maxPages = $totalPages;
}

$delta = 5 - ($totalPages - $startIndex);

if ($totalPages > 5 && $delta > 0 && $delta < 5) {
    $startIndex -= $delta;
}

for ($i = $startIndex; $i < $maxPages; $i++) {
    $currentPageIndex = ($i + 1);
    
    $params = array(
        Store::PAGE_INDEX_KEY_IN_REQUEST => $currentPageIndex
    );
    $url = $controller->getUrl($baseUrl, $params);
    
    if ($currentPageIndex == $currentPage) {
        echo '<li class="active"><a href="#">'.$currentPageIndex.'</a></li>';
        continue;
    }
    
    $className = "page";
    
    if (($currentPage + 1) == $currentPageIndex) {
        $className .= " next-page";
    }
    
?>
    <li><a href="<?php echo $url;?>" class="<?php echo $className; ?>"><?php echo $currentPageIndex; ?></a></li>
<?php
} // end for

//
$disabled = ' disabled';
$url = 'javascript:void(0)';
if ($currentPage < $totalPages) {
    $disabled = '';
    $params = array(
        Store::PAGE_INDEX_KEY_IN_REQUEST => $currentPage + 1
    );
    $url = $controller->getUrl($baseUrl, $params);
}

echo '<li class="next-page next'.$disabled.'"><a href="'.$url.'">'.__('Next').'</a></li>';

//
$disabled = ' disabled';
$url = 'javascript:void(0)';
if ($currentPage != $totalPages) {
    $disabled = '';
    $params = array(
        Store::PAGE_INDEX_KEY_IN_REQUEST => $totalPages
    );
    $url = $controller->getUrl($baseUrl, $params);
}
echo '<li class="last'.$disabled.'"><a href="'.$url.'">'.__('Last').'</a></li>';

?>
</ul>

<?php
if ($pagingMode == "ajax") {
?>
    <script>
        jQuery(document).ready(function() {
            jQuery('.e-body-items').infinitescroll({
                debug           : false, 
                nextSelector    : ".next-page",
                loading: {
                      finishedMsg: '<?php echo __l('No more records'); ?>',
                      msgText: "<?php echo __l('loading more records...'); ?>",
                      img: '<?php echo $engineBaseUrl; ?>images/ajax-loader.gif',
                      prefix: '<tr><td colspan="20" class="db-pager-loader">',
                      postfix: '</td></tr>'
                },
                navSelector     : ".e-db-pager",
                contentSelector : '.e-body-items',
                itemSelector    : '.e-db-table-row'
            }, function (newElements) {
            });
        
            jQuery('.e-db-pager').css({opacity: 1.0, visibility: "visible"}).animate({opacity: 0.0}, 200);
        });
    </script>
<?php
} // end if
?>