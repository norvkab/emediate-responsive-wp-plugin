<?
if(!empty($_POST)){
    ERWP_Options::save($_POST['emediate_options']);
    echo "<h1>Saved:".date("H:i:s")."</h1>";
}
?>

<div class="wrap">

    <div id="icon-options-general" class="icon32"><br/></div>
    <h2>Emediate Responsive Wordpress Plugin</h2>

    <form method="post" action="" >
        <? $emediate_opts = ERWP_Options::load(); ?>
        <div id="icon-options-general" class="icon32"><br/></div>
        <h2>Breakpoints</h2>
        <div id="emediate_breakpoints">
            <table class="widefat">

                <?php
                if(isset($emediate_opts['emediate_options']['breakpoints'])){
                    $i = 0;
                    foreach ($emediate_opts['emediate_options']['breakpoints'] as $opts) {
                        ?>
                        <tr>

                            <td>
                                <strong>Min-width: </strong><input type="text" name="emediate_options[breakpoints][<?php echo $i ?>][min_width]" value="<?= $opts['min_width']?>" />
                            </td>
                            <td>
                                <strong>Max-width: </strong><input type="text" name="emediate_options[breakpoints][<?php echo $i ?>][max_width]" value="<?= $opts['max_width']?>" />
                            </td>
                            <td>
                                <input type="button" class="button-secondary" value="Ta Bort" onclick="EmediateAdmin.remove(jQuery(this).parent().parent())"/>
                            </td>
                            <br/>
                        </tr>
                        <?
                        $i++;
                    }

                }
                ?>
            </table>
        </div>
        <input type="button" class="button-secondary" value="Lägg till ny" onclick="EmediateAdmin.addBreakpoint()" style="margin-top: 12px; margin-bottom: 5px"/>
        <input type="submit" class="button-primary" value="Spara" style="margin-top: 12px; margin-bottom: 5px">
        <div id="icon-options-general" class="icon32"><br/></div>
        <h2>Ads</h2>
        <div id="emediate_ads">
            <table class="widefat">
                <?php
                if(isset($emediate_opts['emediate_options']['ads'])){
                    $i = 0;
                    echo count($emediate_opts['emediate_options']['ads']);
                    foreach ($emediate_opts['emediate_options']['ads'] as $opts) {
                        ?>
                        <tr>
                            <td>
                                <strong>Slug: </strong><input type="text" name="emediate_options[ads][<?php echo $i ?>][slug]" value="<?= $opts['slug']?>" />
                            </td>
                            <?
                                $cus= 0;
                                while(count($emediate_opts['emediate_options']['breakpoints']) > $cus){ ?>
                                    <td>
                                        <strong>CU-<?=$cus?> </strong><input type="text" name="emediate_options[ads][<?php echo $i ?>][cu<?php echo $cus ?>]" value="<?=isset( $opts['cu'.$cus]) ? $opts['cu'.$cus] : ''?>" />
                                    </td>

                               <?   $cus++;
                                }
                            ?>
                            <td>
                                <strong>Implementation: </strong><select type="text" name="emediate_options[ads][<?php echo $i ?>][implementation]" value="<?= $opts['implementation']?>">
                                    <option>
                                        FIF
                                    </option>
                                    <option>
                                        Script
                                    </option>
                                </select>
                            </td>
                            <td>
                                <strong>Status: </strong><select type="text" name="emediate_options[ads][<?php echo $i ?>][status]" value="<?= $opts['status']?>">
                                    <option>
                                        Active
                                    </option>
                                    <option>
                                        Inactive
                                    </option>
                                </select>
                            </td>
                            <td>
                                <strong>Action: </strong><select type="text" name="emediate_options[ads][<?php echo $i ?>][action]" value="<?= $opts['action']?>">
                                    <option>
                                        Yes
                                    </option>
                                    <option>
                                        No
                                    </option>
                                </select>
                            </td>
                            <td>
                                <input type="button" class="button-secondary" value="Ta Bort" onclick="EmediateAdmin.remove(jQuery(this).parent().parent())"/>
                            </td>
                            <br/>
                        </tr>
                        <?
                        $i++;
                    }
                }
                    ?>
            </table>
        </div>

        <input type="button" class="button-secondary" value="Lägg till ny" onclick="EmediateAdmin.addAd()" style="margin-top: 12px; margin-bottom: 5px"/>
        <input type="submit" class="button-primary" value="Spara" style="margin-top: 12px; margin-bottom: 5px">
        <div id="icon-options-general" class="icon32"><br/></div>
        <h2>General</h2>


    </form>
</div>
