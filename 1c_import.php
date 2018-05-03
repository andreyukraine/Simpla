<?php
/**
* 
*/
require_once('api/DGControlSystem.php');
$DG = new DGControlSystem();

//удаляем все товары
//$DG->products->delete_all_product();
//удаляем все группы
//$DG->products->delete_all_categories();



foreach ($_POST as $k => $v) {
         $category_id;
         $request_categories = true;
         $data = removeBOM($v);
         $mass = @json_decode($data, true);

    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            echo ' - Ошибок нет';
            break;
        case JSON_ERROR_DEPTH:
            echo ' - Достигнута максимальная глубина стека';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            echo ' - Некорректные разряды или несоответствие режимов';
            break;
        case JSON_ERROR_CTRL_CHAR:
            echo ' - Некорректный управляющий символ';
            break;
        case JSON_ERROR_SYNTAX:
            echo ' - Синтаксическая ошибка, некорректный JSON';
            break;
        case JSON_ERROR_UTF8:
            echo ' - Некорректные символы UTF-8, возможно неверно закодирован';
            break;
        default:
            echo ' - Неизвестная ошибка';
            break;
    }

    echo PHP_EOL;

        //делаем проверку на существование группы в базе
        if(!empty($mass['groupe'])) {
            print_r('Группа ' . $mass['groupe'] . "\r");
            $query = $DG->db->placehold("SELECT id FROM s_categories WHERE name = ?", $mass['groupe']);
            $DG->db->query($query);
            $request_categories = $DG->db->result();


            //создаем категорию
            echo("Add groupe" . "\r");
            $url_groupe_1 = str_replace(" ","_",$mass['groupe']);
            $url_groupe_2 = str_replace(",","",$url_groupe_1);
            $category = array(
                "name" => $mass['groupe'],
                "visible" => true,
                "url" => $url_groupe_2
            );

            $category_id = $DG->categories->add_category($category);
        }

        if (!empty($category_id)) {
            echo("groupe id " .$category_id. "\r");
            foreach ($mass['produts'] as $r => $p) {

                print_r('продукт ' . $p['name'] . "\r");
                // print_r('артикул ' . $p['art']."\r");

                //создаем сам продукт
                $product = array(
                    "id" => "",
                    "name" => $p['art'],
                    "annotation" => $p['name'],
                    "url" => $p['art'],
                    "category_id" => $category_id,
                    "visible" => true
                );

                $product_id = $DG->products->add_product($product);

                //добавляем картинку
                $file_name = $p['art'] . ".jpg";
                $DG->products->add_image($product_id, $file_name);

                //обновляем категорию товара
                $DG->categories->add_product_category($product_id, $category_id);

                //пробигаемся по характеристикам товарa
                foreach ($p['props'] as $a => $f) {

                    //делаем проверку на наличие
                    if ($f['total'] > 0) {
                        $avaliable = false;
                    } else {
                        $avaliable = true;
                    }

                    $variant = array(
                        "product_id" => $product_id,
                        "pod_zakaz" => $avaliable,
                        "name" => $f['name'],
                        "sku" => $f['sku'],
                        "price" => $f['price'],
                        "price_euro" => $f['priceEUR'],
                        "stock" => $f['total']
                    );
                    $DG->variants->add_variant($variant);
                    print_r('хар ' . $f['name'] . "\r");
                }

            }
        }
}
function removeBOM($data) {
    if (0 === strpos(bin2hex($data), 'efbbbf')) {
        return substr($data, 3);
    }
    return $data;
}
?>