// Campo Detal
$(document).ready(function () {
        $("#_regular_price").keyup(function () {
            var value = $(this).val();
            $("#acf-field_6288f414ab6a6").val(value);
            });
});
// Campo mayorista
$(document).ready(function () {
        $("#festiUserRolePrices_mayorista").keyup(function () {
            var value = $(this).val();
            $("#acf-field_6287c4b23e1e4").val(value).mask('000,000,000');   
            });
});
// Campo Gran mayorista
$(document).ready(function () {
        $("#festiUserRolePrices_granmayorista").keyup(function () {
            var value = $(this).val();
            $("#acf-field_6287c4cd3e1e5").val(value); 
            .mask('000,000,000');  
            });
});
//ocultos
$(".festi-sale-price-block").css("display", "none");
//comas
