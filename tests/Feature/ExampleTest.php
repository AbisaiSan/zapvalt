<?php

test('a aplicação retorna uma resposta bem-sucedida', function () {
    $this->get('/')->assertStatus(200);
});
