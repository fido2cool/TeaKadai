<ul class="ps-list profile-interactions">
    <?php
    foreach ($links as $priority_number => $links) {
        foreach ($links as $link) {
            ?>
            <li <?php

            if ($current == $link['id']) {
                echo ' class="current" ';
            }

            ?>>
                <a href="<?php echo peepso('profile', 'user_link') . '/' . $link['href'];?>">
                    <i class="ps-icon-<?php echo $link['icon'];?>"></i> <span><?php echo $link['title'];?></span>
                </a>
            </li>
        <?php
        }
    }
    ?>
</ul>