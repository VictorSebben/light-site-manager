<h2>Configuração do Sistema</h2>

<div class="config-options">
    <ul>
        <li><a href="#">Meus dados</a></li>

        <!--  TODO testar permissão admin  -->
        <li><a href="#">Gerenciar Usuários</a></li>

        <!--  test if user has permission to manage roles  -->
        <?php if ( $this->editRoles ) : ?>
        <li><a href="<?= $this->Url->make( 'roles/' ) ?>">Gerenciar Roles</a></li>
        <?php endif; ?>
    </ul>
</div>
