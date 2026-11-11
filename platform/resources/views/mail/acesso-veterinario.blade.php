<!doctype html>
<html lang="pt-BR"><body>
<p>Olá, {{ $nome }}.</p>
<p>{{ $laboratorio }} liberou para você o acesso aos pedidos e laudos no Vidra.</p>
<p><a href="{{ route('vet.convite.show', ['token' => $token]) }}">Criar minha senha e acessar</a></p>
<p>Este convite vale por 7 dias e só pode ser usado uma vez. Se você não esperava este acesso, desconsidere este e-mail.</p>
</body></html>
