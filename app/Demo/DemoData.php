<?php

namespace App\Demo;

/**
 * As 12 conversas fictícias do protótipo (docs/prototipo/zapvault-data.js).
 *
 * Cada linha: [data MM-DD, hora HH:MM ou null, remetente, conteúdo]
 *  - remetente: índice em 'parts', 'me' (o usuário) ou 's' (aviso do sistema)
 *  - conteúdo: texto, ou array com 'k' (image, video, audio, doc, sticker, missing, deleted, sys, call)
 */
class DemoData
{
    /** Nome do usuário nas conversas de demonstração, como em Configurações no protótipo. */
    public const ME = 'Moura Consultoria';

    /** Dia "hoje" fixado no protótipo; as datas são deslocadas para a data real. */
    public const PROTOTYPE_TODAY = '2026-10-20';

    private const E = ['k' => 'sys', 'icon' => 'lock', 'text' => 'As mensagens e ligações são protegidas com a criptografia de ponta a ponta.'];

    private const CALL = ['k' => 'call', 'text' => 'Chamada de voz perdida'];

    private static function sys(string $text): array
    {
        return ['k' => 'sys', 'text' => $text];
    }

    /**
     * @return list<array{id: string, name: string, parts: list<string>, file: string, av: int, rows: list<array>}>
     */
    public static function conversations(): array
    {
        return [
            [
                'id' => 'maria', 'name' => 'Maria Souza', 'parts' => ['Maria Souza'], 'file' => 'Conversa com Maria Souza.zip', 'av' => 0,
                'rows' => [
                    ['10-19', null, 's', self::E],
                    ['10-19', '09:14', 0, 'Bom dia! Consegue me mandar a proposta?'],
                    ['10-19', '09:20', 'me', 'Claro, te envio ainda hoje.'],
                    ['10-19', '09:22', 0, 'Ótimo. O evento é no dia 14 de novembro, umas 120 pessoas.'],
                    ['10-19', '09:24', 'me', 'Você tem fotos do espaço?'],
                    ['10-19', '09:30', 0, ['k' => 'image', 'file' => 'IMG-20261019-WA0007.jpg', 'caption' => 'Esse é o espaço', 'hue' => 60, 'ratio' => '4/3']],
                    ['10-19', '09:31', 0, ['k' => 'image', 'file' => 'IMG-20261019-WA0008.jpg', 'hue' => 220, 'ratio' => '3/4']],
                    ['10-19', '09:41', 0, ['k' => 'audio', 'dur' => 42]],
                    ['10-19', '09:50', 'me', 'Ouvi, vou ajustar o prazo.'],
                    ['10-19', '09:52', 'me', 'Só preciso confirmar o som com o fornecedor.'],
                    ['10-19', '10:02', 0, ['k' => 'deleted']],
                    ['10-19', '10:15', 's', self::CALL],
                    ['10-19', '10:16', 0, 'Te liguei, mas pode ser por aqui mesmo.'],
                    ['10-19', '10:18', 0, "Precisamos de:\n• 120 cadeiras\n• uma mesa para o coffee break\n• projetor e telão"],
                    ['10-19', '10:25', 'me', 'Anotado, vai tudo na proposta.'],
                    ['10-19', '14:03', 0, ['k' => 'sticker', 'file' => 'STK-20261019-WA0003.webp', 'hue' => 330]],
                    ['10-19', '17:40', 'me', 'Proposta enviada por e-mail. Qualquer dúvida, me chama.'],
                    ['10-19', '17:52', 0, 'Recebi! Vou passar para o financeiro.'],
                    ['10-20', '08:12', 0, ['k' => 'image', 'file' => 'IMG-20261020-WA0001.jpg', 'caption' => 'Fachada, para o material de divulgação', 'hue' => 160, 'ratio' => '16/9']],
                    ['10-20', '08:55', 0, 'PROPOSTA aprovada pelo financeiro!'],
                    ['10-20', '08:55', 0, 'Só pediram para atualizar os valores do coffee break.'],
                    ['10-20', '08:57', 'me', 'Ótima notícia! Vou preparar o contrato.'],
                    ['10-20', '08:58', 'me', 'E já te mando a versão com os valores novos.'],
                    ['10-20', '09:02', 0, ['k' => 'video', 'file' => 'VID-20261020-WA0002.mp4', 'dur' => 18, 'caption' => 'Montagem do ano passado', 'hue' => 280, 'ratio' => '16/9']],
                    ['10-20', '09:05', 'me', 'Ficou muito bom, dá para repetir.'],
                    ['10-20', '09:08', 'me', ['k' => 'missing']],
                    ['10-20', '09:09', 'me', 'Era a planta baixa. Te reenvio depois.'],
                    ['10-20', '09:12', 0, 'Sem pressa.'],
                    ['10-20', '09:21', 'me', ['k' => 'doc', 'file' => 'Proposta_v3.pdf', 'size' => '240 KB', 'pages' => 3]],
                    ['10-20', '09:21', 'me', "Segue o PDF\ncom os valores atualizados."],
                ],
            ],
            [
                'id' => 'alfa', 'name' => 'Grupo Projeto Alfa', 'parts' => ['Carlos Lima', 'Ana Ribeiro', 'Luciana Prado'], 'file' => 'Conversa com Grupo Projeto Alfa.zip', 'av' => 2,
                'rows' => [
                    ['10-09', null, 's', self::E],
                    ['10-09', '10:00', 's', self::sys('Carlos Lima criou o grupo “Projeto Alfa”')],
                    ['10-09', '10:00', 's', self::sys('Carlos Lima adicionou você, Ana Ribeiro e Luciana Prado')],
                    ['10-09', '10:02', 0, 'Pessoal, criei o grupo para centralizar o projeto Alfa.'],
                    ['10-09', '10:05', 1, 'Boa! Já coloco o cronograma aqui.'],
                    ['10-09', '10:11', 1, ['k' => 'doc', 'file' => 'Cronograma_Alfa.xlsx', 'size' => '86 KB']],
                    ['10-09', '10:20', 'me', 'Obrigado, Ana. Reviso até amanhã.'],
                    ['10-09', '10:31', 2, 'Entrei agora. Qual é o prazo da primeira entrega?'],
                    ['10-09', '10:33', 0, 'Dia 30, se tudo correr bem.'],
                    ['10-13', '14:10', 0, 'Atualizei a planilha com as horas da semana.'],
                    ['10-13', '14:12', 2, ['k' => 'image', 'file' => 'IMG-20261013-WA0011.jpg', 'caption' => 'Quadro da sala de reunião', 'hue' => 100, 'ratio' => '4/3']],
                    ['10-13', '14:15', 'me', 'Fechou. Faltam só os testes da integração.'],
                    ['10-13', '14:20', 1, ['k' => 'audio', 'dur' => 75]],
                    ['10-13', '14:31', 2, 'Concordo com a Ana, melhor adiar a demo uma semana.'],
                    ['10-13', '14:40', 0, 'Ok, vou avisar o cliente.'],
                    ['10-16', '09:03', 1, 'Bom dia! Subi a versão 2 do layout.'],
                    ['10-16', '09:04', 1, ['k' => 'image', 'file' => 'IMG-20261016-WA0004.jpg', 'hue' => 20, 'ratio' => '16/9', 'broken' => true]],
                    ['10-16', '09:10', 2, 'Não abriu aqui, pode reenviar?'],
                    ['10-16', '09:12', 1, ['k' => 'image', 'file' => 'IMG-20261016-WA0005.jpg', 'caption' => 'Agora vai', 'hue' => 250, 'ratio' => '16/9']],
                    ['10-16', '09:20', 0, 'Ficou ótimo. Só aumentaria o contraste do botão.'],
                    ['10-16', '09:25', 'me', 'Ajusto hoje à tarde.'],
                    ['10-16', '15:47', 'me', ['k' => 'doc', 'file' => 'Layout_v2_ajustes.pdf', 'size' => '1,2 MB', 'pages' => 8]],
                    ['10-16', '15:50', 2, ['k' => 'sticker', 'file' => 'STK-20261016-WA0001.webp', 'hue' => 40]],
                    ['10-19', '16:20', 2, 'Alguém confirma a reunião com o cliente?'],
                    ['10-19', '16:31', 1, 'Eu consigo na quinta à tarde.'],
                    ['10-19', '16:33', 'me', 'Quinta às 15h para mim também.'],
                    ['10-19', '16:50', 0, ['k' => 'video', 'file' => 'VID-20261019-WA0001.mp4', 'dur' => 34, 'caption' => 'Gravação da demo', 'hue' => 200, 'ratio' => '16/9']],
                    ['10-19', '17:05', 0, 'reunião confirmada para quinta'],
                ],
            ],
            [
                'id' => 'joao', 'name' => 'João Pereira', 'parts' => ['João Pereira'], 'file' => 'Conversa com João Pereira.txt', 'av' => 7,
                'rows' => [
                    ['10-15', '10:12', 0, 'Oi! Você tem o contato do fornecedor de som?'],
                    ['10-15', '10:30', 'me', 'Tenho sim, é o Sérgio: (98) 98877-1020.'],
                    ['10-19', '16:02', 0, 'Falei com ele, fechamos para o dia 14.'],
                    ['10-19', '16:05', 0, ['k' => 'audio', 'dur' => 23]],
                    ['10-19', '16:10', 'me', 'Que bom! Me avisa se precisar de algo.'],
                    ['10-19', '16:11', 0, 'Perfeito, obrigado!'],
                ],
            ],
            [
                'id' => 'grafica', 'name' => 'Gráfica Rápida', 'parts' => ['Gráfica Rápida'], 'file' => 'Conversa com Gráfica Rápida.zip', 'av' => 4,
                'rows' => [
                    ['10-14', '11:00', 'me', 'Bom dia! Preciso de 500 folders A5, frente e verso.'],
                    ['10-14', '11:05', 'me', 'É para incluir na proposta de um evento.'],
                    ['10-14', '11:40', 0, 'Bom dia! Qual papel e acabamento?'],
                    ['10-14', '11:46', 'me', 'Couché 150 g, sem verniz.'],
                    ['10-16', '11:30', 0, ['k' => 'doc', 'file' => 'Orcamento_4471.pdf', 'size' => '112 KB', 'pages' => 1]],
                    ['10-16', '11:30', 0, 'Orçamento da proposta em anexo'],
                ],
            ],
            [
                'id' => 'fin', 'name' => 'Grupo Financeiro', 'parts' => ['Ana Ribeiro', 'Marcos Teixeira'], 'file' => 'Conversa com Grupo Financeiro.zip', 'av' => 5,
                'rows' => [
                    ['10-15', '17:00', 1, 'As notas fiscais de setembro estão na pasta compartilhada.'],
                    ['10-15', '17:05', 1, ['k' => 'doc', 'file' => 'NF_setembro.zip', 'size' => '3,4 MB']],
                    ['10-15', '17:30', 'me', 'Valeu, Marcos. Falta o extrato do dia 28.'],
                    ['10-16', '10:12', 0, ['k' => 'image', 'file' => 'IMG-20261016-WA0009.jpg', 'caption' => 'Extrato do dia 28', 'hue' => 80, 'ratio' => '3/4']],
                    ['10-16', '10:40', 1, 'Bateu tudo?'],
                    ['10-16', '11:02', 0, 'conciliação fechada'],
                ],
            ],
            [
                'id' => 'fernanda', 'name' => 'Fernanda Lima', 'parts' => ['Fernanda Lima'], 'file' => 'Conversa com Fernanda Lima.txt', 'av' => 3,
                'rows' => [
                    ['10-13', '18:20', 0, 'Oi, tudo bem? Recebi o contrato.'],
                    ['10-13', '18:22', 0, 'Tenho duas dúvidas sobre a cláusula de cancelamento.'],
                    ['10-13', '19:05', 'me', 'Oi, Fernanda! Claro, podemos conversar.'],
                    ['10-14', '08:10', 's', self::CALL],
                    ['10-14', '08:31', 0, 'Pode me ligar amanhã cedo?'],
                ],
            ],
            [
                'id' => 'transp', 'name' => 'Transportadora Norte', 'parts' => ['Transportadora Norte'], 'file' => 'Conversa com Transportadora Norte.txt', 'av' => 6,
                'rows' => [
                    ['10-10', '14:00', 'me', 'Boa tarde! Qual a previsão do pedido 1842?'],
                    ['10-10', '14:22', 0, 'Boa tarde! Previsão de saída na segunda.'],
                    ['10-12', '07:48', 0, 'Pedido 1842 saiu para entrega'],
                ],
            ],
            [
                'id' => 'ricardo', 'name' => 'Ricardo Alves', 'parts' => ['Ricardo Alves'], 'file' => 'Conversa com Ricardo Alves.zip', 'av' => 1,
                'rows' => [
                    ['10-06', '10:00', 'me', ['k' => 'doc', 'file' => 'Proposta_Alves_Eventos.pdf', 'size' => '310 KB', 'pages' => 4]],
                    ['10-06', '10:01', 'me', 'Ricardo, segue a proposta para o lançamento.'],
                    ['10-07', '16:20', 0, 'Recebi, vou apresentar para a diretoria amanhã.'],
                    ['10-08', '11:45', 0, 'Aceitamos a Proposta'],
                ],
            ],
            [
                'id' => 'clinica', 'name' => 'Clínica Vida', 'parts' => ['Clínica Vida'], 'file' => 'Conversa com Clínica Vida.txt', 'av' => 5,
                'rows' => [
                    ['10-01', '16:40', 'me', 'Boa tarde! Gostaria de remarcar a consulta de amanhã para mais tarde.'],
                    ['10-01', '16:55', 0, 'Boa tarde! Temos às 15h ou às 17h30.'],
                    ['10-01', '17:02', 'me', 'Pode ser às 15h.'],
                    ['10-02', '08:05', 0, 'Confirmo o horário das 15h'],
                ],
            ],
            [
                'id' => 'parceiros', 'name' => 'Grupo Parceiros', 'parts' => ['Luciana Prado', 'Diego Martins', 'Helena Castro'], 'file' => 'Conversa com Grupo Parceiros.zip', 'av' => 3,
                'rows' => [
                    ['09-22', '09:00', 's', self::sys('Diego Martins adicionou você')],
                    ['09-22', '09:10', 1, 'Bem-vindos! Aqui vamos alinhar as indicações de clientes.'],
                    ['09-22', '09:30', 2, 'Ótimo. Mando a lista de contatos ainda hoje.'],
                    ['09-25', '15:00', 2, ['k' => 'doc', 'file' => 'Contatos_parceiros.csv', 'size' => '18 KB']],
                    ['09-25', '15:12', 'me', 'Obrigado, Helena!'],
                    ['09-28', '10:40', 0, ['k' => 'doc', 'file' => 'Proposta_Parceria_2027.pdf', 'size' => '520 KB', 'pages' => 6]],
                    ['09-28', '10:41', 0, 'nova PROPOSTA enviada'],
                ],
            ],
            [
                'id' => 'paulo', 'name' => 'Paulo Mendes', 'parts' => ['Paulo Mendes'], 'file' => 'Conversa com Paulo Mendes.txt', 'av' => 7,
                'rows' => [
                    ['09-02', '11:00', 0, 'Oi! Obrigado pela indicação.'],
                    ['09-02', '11:20', 'me', 'Imagina, Paulo. Boa sorte com o projeto.'],
                    ['09-15', '08:02', 0, 'Bom dia, tudo bem?'],
                ],
            ],
            [
                'id' => 'num', 'name' => '+55 98 91234-5678', 'parts' => ['+55 98 91234-5678'], 'file' => 'Conversa com +55 98 91234-5678.zip', 'av' => 6,
                'rows' => [
                    ['09-03', '19:14', 0, ['k' => 'missing']],
                    ['09-03', '19:15', 0, 'Olá, vi o anúncio'],
                ],
            ],
        ];
    }
}
