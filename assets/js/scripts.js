/**
 * scripts for cmr
 */
function jumpToRally()
{
    var selObj = document.getElementById("rally");
    var r = selObj.options[selObj.selectedIndex].value;
    if (r != 'select') {
        window.location = 'index.php?rally='+r;
    }
}
function goToRally(id)
{
    window.location = 'index.php?rally='+id;
}
function jumpToEntry()
{
    var iObj = document.getElementById('m');
    iObj.focus();
}
function deleteEntry(entry_id, rally_id)
{
    if (confirm("are you sure?")) {
        document.forms['deleteForm'].entry_id.value = entry_id;
        document.forms['deleteForm'].rally_id.value = rally_id;
        document.forms['deleteForm'].submit();
    }
}
function checkEntryForm()
{
    var f = document.getElementById("entry");
    if (checkNumber(f.m.value, 1, 1) && checkNumber(f.s.value, 1, 2) && checkNumber(f.cs.value,1 ,2)) {
        return true;
    }
    return false;
}
function checkNumber(n, minn, maxn)
{
    var re;
    var a = arguments;
    if (a.length == 3) {
        re = '^[0-9]{'+a[1]+','+a[2]+'}$';
    } else if (a.length == 2) {
        re = '^[0-9]{'+a[1]+'}$';
    } else {
        return false;
    }
    var ro = new RegExp(re);
    return ro.test(a[0]);
}
function formatNumber(ele)
{
    var no = ele.value;
    if (no.length == 1) {
        ele.value = '0'+no;
    }
}
function goToNext(ele)
{
    var next = '';
    switch(ele.getAttribute('id')) {
        case 'm':
            next = document.getElementById('s');
            if (checkNumber(ele.value,1)) {
                ele.blur();
                next.focus();
            }
            break;
        case 's':
            next = document.getElementById('cs');
            if (checkNumber(ele.value, 2)) {
                ele.blur();
                next.focus();
            }
            break;
        case 'cs':
            next = document.getElementById('enter');
            if (checkNumber(ele.value, 2)) {
                ele.blur();
                next.focus();
            }
            break;
    }        
}
    
        