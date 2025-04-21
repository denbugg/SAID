const product = new Map([
    ['flour', 'Мука'],
    ['meat', 'Конcервы мясные'],
    ['fish', 'Конcервы рыбные'],
    ['sweet', 'Консервы сладкие'],
    ['vegetable', 'Конcервы овощные'],
    ['groats', 'Крупы'],
    ['sugar', 'Сахар'],
    ['pasta', 'Макароны'],
    ['candy', 'Кондитерка'],
    ['fastfood', 'Быстрое питание'],
    ['tea', 'Чай'],
    ['oil', 'Масло растительное']
]);

const gigiena = new Map([
    ['toilet', 'Туалетная бумага'],
    ['toothbrush', 'Зубная щетка'],
    ['wipe', 'Влажные салфетки'],
    ['napkins', 'Средства женской гигиены'],
    ['shampoo', 'Шампуни/гели'],
    ['toothpaste', 'Зубная паста'],
    ['soap', 'Мыло кусковое'],
    ['razor', 'Бритвенные станки'],
]);

const types = new Map([
    ['product', product],
    ['gigiena', gigiena]
]);

function replacer(key, value) {
    if(value instanceof Map) {
        return {
            dataType: 'Map',
            value: Array.from(value.entries()), // or with spread: value: [...value]
        };
    } else {
        return value;
    }
}