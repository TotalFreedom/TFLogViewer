<?php require_once('common.php'); ?>

<!--
    Copyright 2012-2017 Steven Lawson
    Copyright 2017 Aggelos Sarris

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
-->

<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Total Freedom Logviewer</title>
        <!-- STYLES -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.99.0/css/materialize.min.css">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
        <link rel="stylesheet" href="css/index.css" />
    </head>

    <body>
    <div class="container">
        <div id="header">
            <div class="section" style="margin-top:-15px;">

                <strong>Message Filter:</strong>
                <input name="check_toggle" type="button" value="None" class="custom-button" id="check_toggle" />

                <div id="message_filters">
                    <div class="row">
                        <div class="col l2 m5 s12 filtersel">
                            <input type="checkbox" name="message_filter" value="chat_message" id="chat_message" checked="checked" /><label for="chat_message">Chat Messages</label>
                        </div>
                        
                        <div class="col l2 m5 s12 filtersel">
                            <input type="checkbox" name="message_filter" value="login_message" id="login_message" checked="checked" /><label for="login_message">Login Messages</label>
                        </div>

                        <div class="col l2 m5 s12 filtersel">
                            <input type="checkbox" name="message_filter" value="we_command" id="we_command" checked="checked" /><label for="we_command">WorldEdit Commands</label>
                        </div>

                        <div class="col l2 m5 s12 filtersel">
                            <input type="checkbox" name="message_filter" value="normal_command" id="normal_command" checked="checked" /><label for="normal_command">Misc Commands</label>
                        </div>

                        <div class="col l2 m5 s12 filtersel">
                            <input type="checkbox" name="message_filter" value="alert_command" id="alert_command" checked="checked" /><label for="alert_command">Alert Commands</label>
                        </div>
                        <div class="col l2 m5 s12 filtersel">
                            <input type="checkbox" name="message_filter" value="error_message" id="error_message" checked="checked" /><label for="error_message">Errors</label>
                        </div>
                        <div class="col l2 m5 s12 filtersel">
                            <input type="checkbox" name="message_filter" value="preprocess_command" id="preprocess_command" checked="checked" /><label for="preprocess_command">Preprocess Commands</label>
                        </div>
                        <div class="col l2 m5 s12 filtersel">
                            <input type="checkbox" name="message_filter" value="other_message" id="other_message" checked="checked" /><label for="other_message">All Other Messages</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="divider"></div>

            <div class="section filters">
                <div class="row">
                    <div class="col s12">
                        <form action="index.html" id="refresh_form" method="get">
                            <div class="row">
                                <div class="col l3 s6">
                                    <b>Log Size (kb):</b> 
                                    <div class="input-field inline">
                                        <input name="size" type="text" value="64"/>
                                    </div>
                                </div>
                                <div class="col l5 s6">
                                    <b>Text Filter:</b>
                                    <div class="input-field inline">
                                        <input name="text_filter" placeholder="E.g //sphere" type="text" id="text_filter" size="50" maxlength="50"/>
                                    </div>
                                </div>
                                <div class="col l4 s12">
                                <br>
                                    <input id="refresh_btn" class="btn btn-fullwidth" type="submit" value="Refresh Log (Please Wait 5s)" disabled="disabled" />
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="divider"></div><br/>

        </div>
        
        <div class="section" style="padding-top:5px;">
            <div id="log_holder">
                <div id="log_spinner"></div>
                <div id="log_body"></div>
            </div>
        </div>
    </div>


        <!-- SCRIPTS -->
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.99.0/js/materialize.min.js"></script>
        <script src="https://use.fontawesome.com/b5364815ab.js"></script>
        <script type="text/javascript"><?php printf('var gPHPOut = "%s";' . PHP_EOL, Common::getLoginStatus()); ?></script>
        <script type="text/javascript" src="js/index.js"></script>

    </body>
</html>
