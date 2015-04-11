<h2>Users</h2>

<?php if ( $this->objectList != null ): ?>
<table>
    <thead>
    <tr>
        <th>Name</th>
        <th>E-mail</th>
        <th>Categoria</th>
        <th>Status</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($this->objectList as $user): ?>
        <tr>
            <td><?= $user->name; ?></td>
            <td><?= $user->email; ?></td>
            <td><?= $user->getCategory( true ); ?></td>
            <td><?= $user->getStatus( true ); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
    <p class="msg-notice">Não há usuários cadastrados.</p>
<?php endif; ?>
