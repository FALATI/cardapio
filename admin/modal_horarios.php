<div class="modal-body">
    <?php
    $dias = [
        1 => 'Domingo',
        2 => 'Segunda-feira',
        3 => 'Terça-feira',
        4 => 'Quarta-feira',
        5 => 'Quinta-feira',
        6 => 'Sexta-feira',
        7 => 'Sábado'
    ];
    
    $dias_funcionamento = explode(',', $configs['dias_funcionamento'] ?? '2,3,4,5,6');
    $horario_abertura = date('H:i', strtotime($configs['horario_abertura'] ?? '09:00:00'));
    $horario_fechamento = date('H:i', strtotime($configs['horario_fechamento'] ?? '18:00:00'));
    ?>

    <table class="table">
        <thead>
            <tr>
                <th>Dia</th>
                <th>Horário</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dias as $num => $nome): ?>
                <tr>
                    <td><?php echo $nome; ?></td>
                    <td>
                        <?php if (in_array($num, $dias_funcionamento)): ?>
                            <?php echo $horario_abertura . ' às ' . $horario_fechamento; ?>
                        <?php else: ?>
                            <span class="text-danger">Fechado</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>