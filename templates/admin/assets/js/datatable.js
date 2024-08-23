$(document).ready(function () {
    $('[data-toggle="tooltip"]').tooltip();
    var url = $('table').attr('url');
    $('#datatable').DataTable({
        responsive: true
    });
    $('#datatable-keytable').DataTable({ 
        keys: true,
        responsive: true
    });
    $('#datatable-responsive').DataTable({
        responsive: true
    });
    $.extend($.fn.dataTable.defaults, {
        language: {
            url:  url+'templates/admin/assets/js/pt-BR.json'
        },
        initComplete: function (settings, json) {
            $('[tooltip="tooltip"]').tooltip();
        }
    });

    $('#tabelaCategorias').DataTable({
        paging: true,
        buttons: [
            {
                extend: 'copy',
                text: '<u>C</u>opy',
                key: {
                    key: 'c',
                    altKey: true
                }
            }
        ],
        columnDefs: [
            {
                targets: [-1, -2],
                orderable: false
            }
        ],
        order: [[1, 'asc']]
    });
    $('#tabelaBanner').DataTable({
        paging: false,
        columnDefs: [
            {
                targets: [-1, -2],
                orderable: false
            }
        ],
        order: [[1, 'asc']],
        buttons: [
            {
                extend: 'pdf',
                text: '<i class="fa-solid fa-file-pdf"></i> PDF',
                titleAttr: 'Exportar como PDF'
            },
            {
                extend: 'excel',
                text: '<i class="fa-solid fa-file-excel"></i> Excel',
                titleAttr: 'Exportar como Excel'
            }
        ],
    });
    $('#tabelaCongregacao').DataTable({
        order: [[0, 'desc']],
        processing: true,
        paging: false,
        columnDefs: [
            {
                targets: [-1, -2],
                orderable: false
            }
        ],
        order: [[1, 'asc']],
        buttons: [
            {
                extend: 'pdf',
                text: '<i class="fa-solid fa-file-pdf"></i> PDF',
                titleAttr: 'Exportar como PDF'
            },
            {
                extend: 'excel',
                text: '<i class="fa-solid fa-file-excel"></i> Excel',
                titleAttr: 'Exportar como Excel'
            }
        ],
    });
//Tabela de Setores
    $('#tabelaSetores').DataTable({
        paging: true,
        columnDefs: [
            {
                targets: [-1, -2],
                orderable: false
            }
        ],
        order: [[1, 'asc']],
        buttons: [
            {
                extend: 'pdf',
                text: '<i class="fa-solid fa-file-pdf"></i> PDF',
                titleAttr: 'Exportar como PDF'
            },
            {
                extend: 'excel',
                text: '<i class="fa-solid fa-file-excel"></i> Excel',
                titleAttr: 'Exportar como Excel'
            }
        ],
    });

    $('#tabelaLocacoes').DataTable({
        paging: true,
        columnDefs: [
            {
                targets: [-1, -2],
                orderable: false
            }
        ],
        order: [[1, 'asc']],
        buttons: [
            {
                extend: 'pdf',
                text: '<i class="fa-solid fa-file-pdf"></i> PDF',
                titleAttr: 'Exportar como PDF'
            },
            {
                extend: 'excel',
                text: '<i class="fa-solid fa-file-excel"></i> Excel',
                titleAttr: 'Exportar como Excel'
            }
        ],
    });




    
    $('#tabelaPosts').DataTable({
        order: [[0, 'desc']],
        processing: true,
        serverSide: true,
        ajax: {
            url: url + 'admin/posts/datatable',
            type: 'POST',
            error: function (xhr, resp, text) {
                console.log(xhr, resp, text);
            }
        },

        columns: [
            null,
            {
                data: null,
                render: function (data, type, row) {
                    if (row[1]) {
                        return '<a data-fancybox data-caption="Capa" class="overflow zoom" href="' + url + 'uploads/imagens/' + row[1] + '"><img class="thumb" src=" ' + url + 'uploads/imagens/thumbs/' + row[1] + ' " /></a>';
                    } else {
                        return '<i class="fa-regular fa-images fs-1 text-secondary"></i>';
                    }
                }
            },
            null, null, null,
            {
                data: null,
                render: function (data, type, row) {
                    if (row[5] === 1) {
                        return '<i class="fa-solid fa-circle text-success" tooltip="tooltip" title="Ativo"></i>';
                    } else {
                        return '<i class="fa-solid fa-circle text-danger" tooltip="tooltip" title="Inativo"></i>';
                    }
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    var html = '';

                    html += ' <a href=" ' + url + 'admin/posts/editar/' + row[0] + ' " tooltip="tooltip" title="Editar"><i class="fa-solid fa-pen m-1"></i></a> ';

                    html += '<a href=" ' + url + 'admin/posts/deletar/' + row[0] + ' "><i class="fa-solid fa-trash m-1" tooltip="tooltip" title="Deletar"></i></a>';

                    return html;
                }
            }
        ],
        columnDefs: [
            {
                className: 'dt-body-left',
                targets: [2]
            },
            {
                className: 'dt-center',
                targets: [0, 1, 3, 4, 5, 6]
            },
            {
                orderable: false,
                targets: [1, -1]
            }

        ]
    });


    //Tabela de produtos
    $('#tabelaProdutos').DataTable({
        order: [[0, 'desc']],
        processing: true,
        serverSide: true,
        ajax: {
            url: url + 'admin/produtos/datatable',
            type: 'POST',
            error: function (xhr, resp, text) {
                console.log(xhr, resp, text);
            }
        },
        columns: [
            null,
            {
                data: null,
                render: function (data, type, row) {
                    if (row[1]) {
                        return '<a data-fancybox data-caption="Capa" class="overflow zoom" href="' + url + 'uploads/produtos/' + row[1] + '"><img class="thumb" src=" ' + url + 'uploads/produtos/thumbs/' + row[1] + ' " /></a>';
                    } else {
                        return '<i class="fa-regular fa-images fs-1 text-secondary"></i>';
                    }
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    return '<a href="' + url + 'admin/produtos/editar/' + row[0] + '" tooltip="tooltip" title="Editar">' + row[2] + '</a>';
                }
            },
            null,
            {
                data: null,
                render: function (data, type, row) {
                    if (row[5] === '1') {
                        return '<i class="fa-solid fa-circle text-success" tooltip="tooltip" title="Ativo"></i>';
                    } else {
                        return '<i class="fa-solid fa-circle text-danger" tooltip="tooltip" title="Inativo"></i>';
                    }
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    if (row[6] === '1') {
                        return '<span class="badge bg-success">Disponível</span>';
                    } else if (row[6] === '2') {
                        return '<span class="badge bg-warning text-dark">Locado</span>';
                    } else if (row[6] === '3') {
                        return '<span class="badge bg-danger">Em Manutenção</span>';
                    } else {
                        return '<span class="badge bg-secondary">N/A</span>'; // Para qualquer outro valor inesperado
                    }
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    var html = '';
                    html += '<a href="' + url + 'admin/produtos/duplicar/' + row[0] + '" tooltip="tooltip" title="Duplicar produto"><i class="fas fa-copy m-1"></i></a>';
                    html += '<a href="' + url + 'admin/produtos/deletar/' + row[0] + '"><i class="fa-solid fa-trash m-1" tooltip="tooltip" title="Deletar"></i></a>';
                    return html;
                }
            }
        ],
        columnDefs: [
            {
                className: 'dt-body-left',
                targets: [2]
            },
            {
                className: 'dt-center',
                targets: [0, 1, 3, 4, 5, 6]
            },
            {
                orderable: true,
                targets: [1, -1]
            }
        ]
    });
    
    




    //tabela de solicitações
    $('#tabelaSolicitacao').DataTable({
        order: [[0, 'desc']],
        processing: true,
        serverSide: true,
        ajax: {
            url: url + 'admin/solicitacao/datatable',
            type: 'POST',
            error: function (xhr, resp, text) {
                console.log(xhr, resp, text);
            }
        },

        columns: [
            null, null, null,
            {
                data: null,
                render: function (data, type, row) {
                    console.log(row);
                    if (row[3] === '1') {
                        return '<span class="text-warning">Aberto</span>';
                    } else if (row[3] === '2') {
                        return '<span class="text-info">Em andamento</span>';
                    } else if (row[3] === '4') {
                        return '<span class="text-danger">Cancelada</span>';
                    } else {
                        return '<span class="text-success">Concluído</span>';
                    }
                }
            },
            {
                data: null,
                render: function (data, type, row) {

                    if (row[4] === '1') {
                        return ' <i class="fa-solid fa-circle text-success" tooltip="tooltip" title="Baixa"></i>';
                    } else if (row[4] === '2') {
                        return '<i class="fa-solid fa-circle text-warning" tooltip="tooltip" title="Média"></i>';
                    } else {
                        return '<i class="fa-solid fa-circle text-danger" tooltip="tooltip" title="Alta"></i>';
                    }
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    var html = '';
                    html += '<form id="formParecer" action="' + url + 'admin/solicitacao/statusChamado/' + row[0] + '" method="post">'; // Substitua "URL_DA_SUA_ACAO" pela URL correspondente
                    html += '<a href="#info' + row[0] + ' " data-bs-toggle="offcanvas" tooltip="tooltip" title="log"><i class="fa-solid fa-arrow-trend-up"></i></a> ';
                    html += '<div class="offcanvas offcanvas-start" style="width: 50%;" tabindex="-1" id="info' + row[0] + '">'
                    html += '<div class="offcanvas-header">'
                    html += '<h5 class="offcanvas-title" id="offcanvasExampleLabel"></h5>'
                    html += '<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>'
                    html += '</div>'
                    html += '<div class="offcanvas-body">'
                    html += '<ul class="list-group list-group-flush">'
                    html += '<h3>Informações do chamado</h3>'
                    html += '<li class="list-group-item">'
                    html += 'Cadastrado à: ' + row[5]
                    html += '</li>'
                    html += '<li class="list-group-item">'
                    html += 'Cadastrado por: ' + row[2]
                    html += '</li>'
                    html += '<li class="list-group-item">'
                    html += 'Prioridade: ' + (row[4] === '1' ? '<i class="fa-solid fa-circle text-success" tooltip="tooltip" title="Baixa"></i> baixa' : row[4] === '2' ? '<i class="fa-solid fa-circle text-warning" tooltip="tooltip" title="Baixa"></i> Média' : '<i class="fa-solid fa-circle text-danger" tooltip="tooltip" title="Baixa"></i> Alta')
                    html += '</li>'
                    html += '<li class="list-group-item">'
                    html += 'Status do chamado: <b>' + (row[3] === '1' ? '<span class="text-warning">Aberto</span>' : row[3] === '2' ? '<span class="text-info">Em andamento</span>' : row[3] === '4' ? '<span class="text-danger">Cancelada</span>' : '<span class="text-success">Concluída</span>') + '</b>'
                    html += '</li>'
                    html += '<li class="list-group-item">'
                    html += 'Descrição do chamado: <b>' + row[6] + '</b>'
                    html += '</li>'
                    html += '<li class="list-group-item">'
                    html += 'Parecer técnico:<br> <textarea class="form-control" id="parecerInput' + row[0] + '" name="parecer">' + row[9] + '</textarea>'
                    html += '</li>'
                    html += '<li class="list-group-item">'
                    html += 'Valor da Hora: <br>'
                    html += '<input type="text" class="form-control" id="valorHoraInput" name="valor_hora" value="'+row[10]+'">'
                    html += '</li>'

                    html += '<li class="list-group-item">';
                    html += 'Status:<br>';
                    html += '<select class="form-select" id="statusSelect' + row[0] + '" name="status">';

                    // Opção Aberto
                    html += '<option value="1"' + (row[3] === '1' ? ' selected' : '') + '>Aberto</option>';

                    // Opção Em Andamento
                    html += '<option value="2"' + (row[3] === '2' ? ' selected' : '') + '>Em Andamento</option>';

                    // Opção Concluído
                    html += '<option value="3"' + (row[3] === '3' ? ' selected' : '') + '>Concluído</option>';

                    // Opção Cancelada
                    html += '<option value="4"' + (row[3] === '4' ? ' selected' : '') + '>Cancelada</option>';

                    html += '</select>';
                    html += '</li>';
                    // Verifica se há uma foto disponível
                    if (row[7]) {
                        html += '<li class="list-group-item">';
                        html += 'Foto do chamado: <br>';
                        html += '<a data-fancybox="gallery" data-caption="Foto do chamado" class="overflow zoom" href="' + url + '/uploads/cliente/' + row[7] + '">';
                        html += '<img src="' + url + '/uploads/cliente/thumbs/' + row[7] + '" alt="Foto do chamado" style="max-width: 100%;">';
                        html += '</a>';
                        html += '</li>';
                    }

                    // Verifica se há um vídeo disponível
                    if (row[8]) {
                        html += '<li class="list-group-item">';
                        html += 'Vídeo do chamado: <br>';
                        html += '<div style="max-width: 100%; overflow: hidden;">';
                        html += '<video controls width="100%" height="auto">';
                        html += '<source src="' + url + 'uploads/videos/' + row[8] + '" type="video/mp4">';
                        html += 'Seu navegador não suporta o elemento de vídeo.';
                        html += '</video>';
                        html += '</div>';
                        html += '</li>';
                    }

                    html += '<li class="list-group-item">';
                    html += '<button type="submit" class="btn btn-primary">Gravar Parecer</button>';
                    html += '</li>';

                    // Adicione aqui o código para exibir a foto e o vídeo
                    html += '</ul>'
                    html += '</div>'
                    html += '</div>'
                    console.log(url + '/uploads/cliente/' + row[7]);
                    html += '</form>'; // Fechando o formulário

                    return html;
                }
            }

        ],
        columnDefs: [
            {
                className: 'dt-body-left',
                targets: [2]
            },
            {
                className: 'dt-center',
                targets: [1, 2, 3, 4, 5]
            },
            {
                orderable: false,
                targets: [1, 3, -1]
            }

        ]
    });

    $(document).ready(function() {
        // Função genérica para capturar o termo de busca e abrir a URL
        function exportData(type) {
            // Capturar o ID da tabela atual
            var tableId = $('table').attr('id');
            var searchTerm = $('#' + tableId + '_filter input').val();
            var urlParams = new URLSearchParams(window.location.search);
            
            var baseUrl = type === 'pdf' ? 'gerar-pdf/' : 'gerar-excel/';
            
            if (!urlParams.has('searchTerm')) {
                // Se searchTerm não estiver presente na URL, adicionar como parâmetro e abrir em uma nova aba
                window.open(baseUrl + (searchTerm === '' ? 'todos' : searchTerm), '_blank');
            } else {
                // Se searchTerm já estiver presente na URL, abrir em uma nova aba sem adicionar novamente o parâmetro
                window.open(baseUrl + 'todos', '_blank');
            }
        }
    
        $('#botaoExportarPDF').on('click', function() {
            exportData('pdf');
        });
    
        $('#botaoExportarExcel').on('click', function() {
            exportData('excel');
        });
    });
  
    //TABELA USUÁRIOS
    $('#tabelaUsuarios').DataTable({
        order: [[1, 'asc']],
        processing: true,
        serverSide: true,
        ajax: {
            url: url + 'admin/usuarios/datatable',
            type: 'POST',
            error: function (xhr, resp, text) {
                console.log(xhr, resp, text);
            }
        },

        columns: [
            null, null, null,
            {
                data: null,
                render: function (data, type, row) {
                    if (row[3] === '3') {
                        return '<span class="text-danger">Administrador</span>';
                    } else {
                        return '<span class="text-secondary">Usuário</span>';
                    }
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    if (row[4] === '1') {
                        return '<i class="fa-solid fa-circle text-success" tooltip="tooltip" title="Ativo"></i>';
                    } else {
                        return '<i class="fa-solid fa-circle text-danger" tooltip="tooltip" title="Inativo"></i>';
                    }
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    var html = '';

                    html += ' <a href=" ' + url + 'admin/usuarios/editar/' + row[0] + ' " tooltip="tooltip" title="Editar"><i class="fa-solid fa-pen m-1"></i></a> ';

                    html += '<a href=" ' + url + 'admin/usuarios/deletar/' + row[0] + ' "><i class="fa-solid fa-trash m-1" tooltip="tooltip" title="Deletar"></i></a>';

                    return html;
                }
            }
        ],
        columnDefs: [
            {
                className: 'dt-body-left',
                targets: [1, 2]
            },
            {
                className: 'dt-center',
                targets: [3, 4, 5]
            },
            {
                orderable: false,
                targets: [-1]
            }

        ]
    });

});