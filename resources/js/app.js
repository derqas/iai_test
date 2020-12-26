require('./bootstrap');

window.$ = window.jQuery = require('jquery');
window.T = require('twig');


templation_path = '/api/get_templation/';
error_templation = '{% for error in errors %}<div class="row"><div class="col bg-danger">{{error|e}}</div></div>{% endfor %}';
error_container = 'error_container';
templations = {};
templations_to_load = [
    'main',
    'add',
    'view'
];


    function error_worker(errors) {
        if (errors.length > 0) {
            if (error_container != null && error_templation != null) {
                $('div#' + error_container).empty();
                $('div#' + error_container).append(error_templation.render({errors: errors}));
            }
        } else {
            if (this.error_container != null && this.error_templation != null) {
                $('div#' + this.error_container).empty();
            }
        }
    }

    function after_main(parameter){//second part of start
        get_data('/api/list',{},'list');
    }

    function after_add(data={}) {
        $('div#main').empty();
        $('div#main').append(templations['add'].render());
        $('button#invoice_add').click(function () {
            var positions_nodes = $('div#position_view');
            var positions = [];
            positions_nodes.each(function () {
                pos = {};
                pos.name = $(this).find('div#name').text();
                pos.unit = $(this).find('div#unit').text();
                pos.quantity = $(this).find('div#quantity').text();
                pos.price = $(this).find('div#price').text();
                pos.sum = $(this).find('div#sum').text();
                positions[positions.length] = pos;
            });
            var invoice = {};
            invoice.positions = positions;
            invoice.number = $('input#invoice_number').val(); // !!name not number!!
            invoice.date = $('input#invoice_date').val();
            invoice.total = parseFloat($('input#invoice_total').val());
            get_data('/api/create',{invoice:invoice},'list');
        });
        $('button#invoice_cancel').click(function () {
            get_templation('main');
        });
        get_templation('position_add');
    }
    function after_position_add(data = {}) {
        $('div#positions').append(templations['position_add'].render());
        if (data.position != undefined){
            $('input#position_add_name').val(data.position.name);
            $('input#position_add_unit').val(data.position.unit);
            $('input#position_add_quantity').val(data.position.quantity);
            $('input#position_add_price').val(data.position.price);
            $('input#position_add_sum').val(data.position.sum);
        }
        $('button#position_add').click(function () {
            var data = {position:{}};
            data.position.name = $('input#position_add_name').val();
            data.position.unit = $('input#position_add_unit').val();
            data.position.quantity = parseFloat($('input#position_add_quantity').val());
            data.position.price = parseFloat($('input#position_add_price').val());
            data.position.sum = parseFloat($('input#position_add_sum').val());
            var total = parseFloat(data.position.sum);
            var sums = $('div#sum');
            if (sums.length > 0) {
                sums.each(function (elem) {
                    total = total + parseFloat($(this).text());
                });
            }
            $('input#invoice_total').val(total);
            $('div#position_add').remove();
            get_templation('position_view',data);
        });
        get_templation('position_view');
    }
    function after_position_view(data) {
        if (data.position != undefined){
            $('div#positions').append(templations['position_view'].render({position:data.position}));
            $('button#position_edit').click(function () {
                var parent = $(this).parent().parent();
                var position = {};
                position.name = $(parent).find('div#name').text();
                position.unit = $(parent).find('div#unit').text();
                position.quantity = parseFloat($(parent).find('div#quantity').text());
                position.price = parseFloat($(parent).find('div#price').text());
                position.sum = parseFloat($(parent).find('div#sum').text());
                parent.remove();
                $('div#position_add').remove();
                after_position_add({position:position});
            });
            $('button#position_delete').click(function () {
                $(this).parent().parent().remove();
                var total = parseFloat(data.position.sum);
                var sums = $('div#sum');
                if (sums.length > 0) {
                    sums.each(function (elem) {
                        total = total + parseFloat($(this).text());
                    });
                }
                $('input#invoice_total').val(total);
            });
            after_position_add();
        }
    }
    function after_view(data) {
        get_data('/api/view/'+data.invoice_id,{},'view');
    }
    function view(data) {
        get_templation('position_simple',data);
    }
    function after_position_simple(data) {
        $('div#main').empty();
        var templation = templations['view'];
        $('div#main').append(templation.render({invoice:data.invoice}));
        if (Array.isArray(data.invoice.positions)) {
            data.invoice.positions.forEach(function (position) {
                $('div#positions').append(templations['position_simple'].render({position: position}));
            });
        }
        $('button#invoice_close').click(function () {
            get_templation('main');
        });
    }
    function list(data) {
        $('div#main').empty();
        var templation = templations['main'];
        $('div#main').append(templation.render({invoices:data.invoices}));
        $('button#add').click(function () {
            get_templation('add');
        });
        $('button#view').click(function () {
            get_templation('view',{invoice_id:$(this).attr('rel')});
        });
        $('button#delete').click(function () {
            get_data('/api/delete/'+$(this).attr('rel'),{},'list');
        });
    }


function get_templation(templation_id,parameter = {}){
    if (templations[templation_id] != undefined){
        eval('after_' + templation_id+'(parameter)');
    }else {
        $.get(templation_path + templation_id, function (data) {
        }, 'json').always(function (data) {
            error_worker(data.errors);
            if (data.errors.length == 0) {
                templations[templation_id] = T.twig({data: data.content});
                eval('after_' + templation_id+'(parameter)');
            }
        });
    }
}
function get_data(path,params,next){
    if (params._token == undefined){params._token = $('input#_token').val();}
    var link = this;
    console.log(params);
    $.post(path,params,function (data) {

    },'json').always(function (data) {
        error_worker(data.errors);
        var funct = next != 'list' && data.type == 'list'?'list':next;
        console.log(next != 'list' && data.type == 'list'?'list':next);
        console.log(link);
        eval(funct+'(data)');
    });
}

$(document).ready(function () {
    get_templation('main', {});
});


