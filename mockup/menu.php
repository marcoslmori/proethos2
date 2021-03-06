<nav class="navbar navbar-default navbar-fixed-top">
    <div class="container-fluid">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#"><b>PRO</b>ETHOS<b>2</b></a>
        </div>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
                
                <li>
                    <p class="navbar-btn">
                        <a href="nova-submissao.php" class="btn btn-primary">Nova Submissão</a>
                    </p>
                </li>
                <li class="active"><a href="index.php">Home</a></li>
                <li class=""><a href="submissoes.php">Submissões</a></li>
                
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Comitê <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="comite-submissoes.php">Submissões</a></li>
                        <li role="separator" class="divider"></li>
                        <!-- <li><a href="#">Alterar Protocolos Manualmente</a></li> -->
                        <li><a href="comite-relatores.php">Relatores</a></li>
                        <li><a href="comite-perguntas-frequentes.php">Perguntas Frequentes</a></li>
                        <li><a href="comite-reunioes.php">Reuniões</a></li>
                    </ul>
                </li>
                
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Relatórios <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="#">Indicadores</a></li>
                        <li><a href="#">Monitoramento de Protocolos</a></li>
                        <li><a href="#">Listagem de Investigadores</a></li>
                        <li><a href="#">Membros do Comitê</a></li>
                        <li role="separator" class="divider"></li>
                        <li><a href="#">Reuniões</a></li>
                    </ul>
                </li>

                <li class=""><a href="faq.php">FAQ</a></li>
                <li class=""><a href="documentos.php">Documentos</a></li>
                <li class=""><a href="contato.php">Contato</a></li>

            </ul>

            <ul class="nav navbar-nav navbar-right">
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class='glyphicon glyphicon-flag'></i> <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class='active'><a href="#">Português</a></li>
                        <li><a href="#">English</a></li>
                        <li><a href="#">Español</a></li>
                    </ul>    
                </li>

                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class='glyphicon glyphicon-user'></i> <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="#" data-toggle='modal' data-target='#modal-edit-personal-information'>Seu Perfil</a></li>
                        <li role="separator" class="divider"></li>
                        <li><a href="#">Logout</a></li>
                    </ul>    
                </li>
            </ul>
        </div><!-- /.navbar-collapse -->
    </div><!-- /.container-fluid -->
</nav>

<!-- Modal Seu Perfil -->
<div class="modal fade" id="modal-edit-personal-information" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Suas Informações Pessoais</h4>
            </div>
            <div class="modal-body">
                <form class='form'>
                    <div class='row'>
                        <div class='col-md-12'>
                            <div class="form-group">
                                <label for="">Nome Completo:</label> 
                                <a href='#' data-toggle="modal" data-target="#modal-help"><i class='glyphicon glyphicon-question-sign'></i></a>
                                <input type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                            
                    <div class='row'>
                        <div class='col-md-6'>
                            <div class="form-group">
                                <label for="">E-mail:</label> 
                                <a href='#' data-toggle="modal" data-target="#modal-help"><i class='glyphicon glyphicon-question-sign'></i></a>
                                <input type='text' class='form-control'>
                            </div>
                        </div>

                        <div class='col-md-6'>
                            <div class="form-group">
                                <label for="">E-mail alternativo:</label> 
                                <a href='#' data-toggle="modal" data-target="#modal-help"><i class='glyphicon glyphicon-question-sign'></i></a>
                                <input type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                    
                    <div class='row'>
                        <div class='col-md-6'>
                            <div class="form-group">
                                <label for="">Senha:</label> 
                                <a href='#' data-toggle="modal" data-target="#modal-help"><i class='glyphicon glyphicon-question-sign'></i></a>
                                <input type='text' class='form-control'>
                            </div>
                        </div>

                        <div class='col-md-6'>
                            <div class="form-group">
                                <label for="">Senha:</label> 
                                <a href='#' data-toggle="modal" data-target="#modal-help"><i class='glyphicon glyphicon-question-sign'></i></a>
                                <input type='text' class='form-control'>
                            </div>
                        </div>
                    </div>

                    <div class='row'>
                        <div class='col-md-12'>
                            <div class="form-group">
                                <label for="">Endereço:</label> 
                                <a href='#' data-toggle="modal" data-target="#modal-help"><i class='glyphicon glyphicon-question-sign'></i></a>
                                <textarea rows="4" class='form-control'></textarea>
                            </div>
                        </div>
                    </div>

                    <div class='row'>
                        <div class='col-md-12'>
                            <div class="form-group">
                                <label for="">Instituição:</label> 
                                <a href='#' data-toggle="modal" data-target="#modal-help"><i class='glyphicon glyphicon-question-sign'></i></a>
                                <input type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary">Salvar</button>
            </div>
        </div>
    </div>
</div>