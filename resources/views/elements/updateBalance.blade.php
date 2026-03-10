<?php
                            $completeNumber = number_format($balance, 2);
//                            $newstring = substr($completeNumber, -3);
                            echo substr($completeNumber, 0, -3);
                            echo '<sup>' . substr($completeNumber, -3) . '</sup>';
                            ?>