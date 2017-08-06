/*
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
*/

var gRefreshWait;
var gRefreshTimerID;
var gCheckAll = true;

$(document).ready(function ()
{
    if (top.location != location)
    {
        top.location.href = document.location.href;
    }

    $("#refresh_form").submit(function ()
    {
        if (gRefreshWait <= 0)
        {
            updateLog();
        }

        return false;
    });

    $("input[name='message_filter']").change(function ()
    {
        updateFilters();
    });

    // $(window).resize(function ()
    // {
    //     $("#log_holder").height(($(window).height() - $("#header").height()) - 40);
    // });

    // $("#log_holder").height(($(window).height() - $("#header").height()) - 40);

    $("#check_toggle").click(function ()
    {
        gCheckAll = !gCheckAll;
        updateCheckAll();
    });

    updateLog();
});

function updateFilters()
{
    $("input[name='message_filter']").each(function ()
    {
        if ($(this).is(':checked'))
        {
            some_checked = true;

            $('.' + $(this).val()).show();
        }
        else
        {
            $('.' + $(this).val()).hide();
        }
    });
}

function updateLog()
{
    $("#log_body").html('');
    $('#log_spinner').show();
    $("#refresh_btn").prop('disabled', true);
    $("#refresh_btn").val("Refreshing...")

    $.ajax({
        url: 'pull_log.php', dataType: 'html', data: $("#refresh_form").serialize(), type: 'POST',
        success: function (data, textStatus, XMLHttpRequest)
        {
            $('#log_spinner').hide();
            $("#log_body").html(gPHPOut + data);
            $("#log_holder").scrollTop($("#log_holder")[0].scrollHeight);
            start_wait_timer();
            updateFilters();
        },
        error: function (XMLHttpRequest, textStatus, errorThrown)
        {
            $('#log_spinner').hide();
            $("#log_body").html(textStatus);
            start_wait_timer();
        }
    });
}

function start_wait_timer()
{
    gRefreshWait = 5;

    gRefreshTimerID = window.setInterval(function ()
    {
        if (--gRefreshWait <= 0)
        {
            $("#refresh_btn").prop('disabled', false);
            $("#refresh_btn").val("Refresh Log");
            clearInterval(gRefreshTimerID);
        }
        else
        {
            $("#refresh_btn").val("Refresh Log (Please Wait " + gRefreshWait + "s)");
        }
    }
    , 1000);
}

function updateCheckAll()
{
    if (gCheckAll)
    {
        $("#message_filters input:checkbox").prop('checked', true);
        $("#check_toggle").val("None");
    }
    else
    {
        $("#message_filters input:checkbox").prop('checked', false);
        $("#check_toggle").val("All");
    }

    updateFilters();
}

var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-12595157-6']);
_gaq.push(['_trackPageview']);

(function () {
    var ga = document.createElement('script');
    ga.type = 'text/javascript';
    ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(ga, s);
})();
