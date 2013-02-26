function start_throbber( ) {
    throb = null;
    if (document.all) {
        throb = document.all['throbber'];
    }
    if (!throb && !(throb = document.getElementById('throbber'))) {
        if (!(reportdiv = document.getElementById('php_report_block')) ||
            !(reportkids = reportdiv.getElementsByTagName('div')) ||
            !(nextelem = reportkids[0].getElementsByTagName('*'))
        ) {
            return;
        }
        throb = document.createElement('DIV');
        throb.name = 'throbber';
        reportkids[0].insertBefore(throb, nextelem[0]);
    }
    window.scrollTo(0,0);
    throb.innerHTML = '<center><img src="/rlmoodle.plaintest.git/blocks/php_report/pix/throbber_loading.gif" /></center>';
}
