<?
if(!empty($_POST['emediate_options'])){
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
                if(isset($emediate_opts['breakpoints'])){
                    $i = 0;
                    foreach ($emediate_opts['breakpoints'] as $opts) {
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
                if(isset($emediate_opts['ads'])){
                    $i = 0;
                    echo count($emediate_opts['ads']);
                    foreach ($emediate_opts['ads'] as $opts) {
                        ?>
                        <tr>
                            <td>
                                <strong>Slug: </strong><input type="text" name="emediate_options[ads][<?php echo $i ?>][slug]" value="<?= $opts['slug']?>" />
                            </td>
                            <?
                                $cus= 0;
                                while(count($emediate_opts['breakpoints']) > $cus){ ?>
                                    <td>
                                        <strong>CU-<?=$cus?> </strong><input type="text" name="emediate_options[ads][<?php echo $i ?>][cu<?php echo $cus ?>]" value="<?=isset( $opts['cu'.$cus]) ? $opts['cu'.$cus] : ''?>" />
                                    </td>

                               <?   $cus++;
                                }
                            ?>
                            <td>
                                <strong>Implementation: </strong>
                                <select type="text" name="emediate_options[ads][<?php echo $i ?>][implementation]" ?>">
                                    <option <? if($opts['implementation'] == 'FIF') echo 'selected = selected'; ?> value="FIF">
                                        FIF
                                    </option>
                                    <option <? if($opts['implementation'] == 'JS') echo 'selected = selected'; ?> value="JS">
                                        JS
                                    </option>
                                </select>
                            </td>
                            <td>
                                <strong>Status: </strong>
                                <select type="text" name="emediate_options[ads][<?php echo $i ?>][status]" ">
                                    <option <? if($opts['status'] == 'Active') echo 'selected = selected'; ?> value="Active">
                                        Active
                                    </option>
                                    <option <? if($opts['status'] == 'Inactive') echo 'selected = selected'; ?> value="Inactive">
                                        Inactive
                                    </option>
                                </select>
                            </td>
                            <td>
                                <strong>Action: </strong>
                                <select type="text" name="emediate_options[ads][<?php echo $i ?>][action]"">
                                    <option <? if($opts['action'] == 'Yes') echo 'selected = selected'; ?> value="Yes">
                                        Yes
                                    </option>
                                    <option <? if($opts['action'] == 'No') echo 'selected = selected'; ?> value="No">
                                        No
                                    </option>
                                </select>
                            </td>
                            <td>
                                <strong>Height: </strong><input type="text" name="emediate_options[ads][<?php echo $i ?>][height]" value="<?= $opts['height']?>" />
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
        <table>
            <tr>
               <td>
                   <strong>Default_js_host: </strong>
               </td>
               <td>
                   <input type="text" name="emediate_options[default_js_host]" value="<?= $emediate_opts['default_js_host']?>" />
               </td>
            </tr>
            <tr>
                <td>
                    <strong>Cu_param_name: </strong>
                </td>
                <td>
                    <input type="text" name="emediate_options[cu_param_name]" value="<?= $emediate_opts['cu_param_name']?>" />
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Empty_ad_tags: </strong>
                </td>
                <td>
                    <textarea type="text" name="emediate_options[empty_ad_tags]" ><?= $emediate_opts['empty_ad_tags']?></textarea>
                </td>
            </tr>
            <tr>
                <td>
                    <input type="submit" class="button-primary" value="Spara" style="margin-top: 12px; margin-bottom: 5px">
                </td>
                <td></td>
            </tr>

        </table>

    </form>
</div>
