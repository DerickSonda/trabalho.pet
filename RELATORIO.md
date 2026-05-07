# Relatório — Parte 2 do Trabalho
## Diário de Pets: análise crítica do uso de IA no desenvolvimento

**Aluno:** Derick Sonda
**Disciplina:** (preencher)
**Data:** maio de 2026

---

## 1. Ferramentas Utilizadas

A ferramenta principal foi o **Claude Code** rodando dentro da extensão do VS Code, com o modelo Claude Opus 4.7. Escolhi essa configuração por dois motivos práticos. Primeiro, ela tem acesso direto aos arquivos do projeto, então eu não preciso copiar e colar código no chat: ela edita os arquivos e eu vejo o diff antes de aprovar. Segundo, ela executa comandos de terminal sob minha supervisão (`git`, `php -l`, etc.), o que poupa o vai-e-vem entre janelas.

Não usei o ChatGPT pra essa parte do trabalho. Comparado com o que vi colegas usando, achei o fluxo do Claude Code mais produtivo justamente por evitar o copia-cola. Em paralelo, mantive o XAMPP rodando e ia abrindo `http://localhost/trabalho.pet/public/` no navegador a cada etapa pra testar antes de seguir.

## 2. Estratégia de Prompts

Optei por dividir o trabalho em etapas pequenas e funcionais, e não em um único prompt gigante. As etapas ficaram assim, e cada uma virou um commit no git:

1. Estrutura inicial, schema do banco e conexão PDO (`b1136ad`).
2. Sistema de autenticação: login, cadastro, logout (`849c754`).
3. Dashboard e CRUD de pets, com upload de foto (`f825be1`).
4. CRUD de registros com filtros por pet, tipo e período (`be505e8`).
5. Polimento visual (`2c0ebdc`), depois que tudo já estava funcionando.

A última eu chamei mentalmente de "etapa 4.5", porque pedi quando o sistema já estava pronto. Achei que tinha ficado simplório demais visualmente e quis melhorar.

O motivo dessa divisão é simples: em cada etapa eu testo no navegador antes de seguir. Se algo quebra, é mais fácil achar a causa em sete arquivos do que em trinta. Outra coisa que ajudou foi escrever **regras gerais no primeiro prompt** ("sempre PDO com prepared statements", "senha sempre com `password_hash`", "`htmlspecialchars` em toda saída", "validação dupla cliente e servidor"). Sem essa lista no início, eu teria que repetir as exigências o tempo todo.

Também pedi um arquivo de log informal (`DEV_LOG.md`) onde a IA registrasse os problemas reais que enfrentou em cada etapa. Esse arquivo virou a base deste próprio relatório, e foi onde eu pude notar como ela explica decisões para si mesma.

## 3. Pontos Positivos

Vou citar quatro situações que me chamaram a atenção.

**Boas práticas de segurança aplicadas sem eu pedir explicitamente.** No upload de foto da etapa 3, eu pedi pra "validar extensão, MIME e tamanho". A IA, por conta própria, separou o `$_FILES['type']` (que vem do navegador e é fácil de forjar) do MIME *real* lido com `mime_content_type()` direto dos bytes do arquivo. O comentário no código diz "o browser manda o que quiser no Content-Type". Eu até sabia disso de cabeça, mas se eu tivesse aceitado a primeira solução óbvia, o sistema estaria mais frágil. Ela também trocou o nome original do arquivo por `uniqid('pet_', true) . '.ext'` antes de salvar, evitando nomes maliciosos e colisão.

**Defesa em profundidade.** Na etapa 4, percebi que o `DELETE` de registro tinha `WHERE r.id = :id AND p.usuario_id = :uid` *mesmo* depois de já ter passado por `buscarRegistroDoUsuario()`, que faz a checagem de propriedade. Quando perguntei se a verificação dupla não era exagero, a explicação registrada no DEV_LOG foi: "se um dia alguém editar essa função e deixar passar, o `DELETE` ainda barra". É o tipo de redundância que um aluno tende a remover por achar desnecessário, e que numa auditoria de verdade salva a pele.

**Detalhe na função de cálculo de idade.** Pedi simplesmente "idade calculada a partir de `data_nascimento`". Ela usou `DateTime::diff` e ainda colocou um *fallback*: se o pet tem menos de um mês, mostra em dias ("12 dias") em vez de "0 anos e 0 meses". Eu não pedi isso, mas faz sentido pra filhote.

**Senha e XSS desde o começo.** No cadastro o hash usa `password_hash($senha, PASSWORD_DEFAULT)`. O comentário no código justifica: "deixa o PHP escolher o algoritmo mais atual, hoje bcrypt e amanhã pode ser argon2 sem mudar nada". E em todo formulário, qualquer variável dinâmica saída no HTML passa por uma função `escapar()` que faz `htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8')`. Não tem nenhum `echo $_POST[...]` direto em lugar nenhum.

## 4. Desafios e Pontos Negativos

Aqui também tive coisas concretas pra anotar.

**Bug do flash no logout.** Esse foi o erro mais nítido. Depois de fazer logout, era pra aparecer "Você saiu com sucesso" na tela de login. Não aparecia. Fui investigar e a IA tinha chamado `deslogarUsuario()` (que faz `session_destroy()`) e logo em seguida tentado gravar a flash em `$_SESSION`. Só que sem sessão ativa, nada persiste. A correção foi chamar `iniciarSessao()` *de novo* depois de destruir, antes de gravar a mensagem. Esse bug só apareceu quando eu testei manualmente. A IA não roda o sistema, então não detectou sozinha. Anotação importante: confiar no código que a IA gera sem testar é arriscado mesmo em fluxos que parecem triviais.

**Erro bobo no formato de data.** Na etapa 4, o formulário de registro dava "Data/hora inválida" mesmo digitando uma data correta. A IA tinha escrito `DateTime::createFromFormat('Y-m-d H:i', ...)` (com espaço), quando o input HTML do tipo `datetime-local` manda no formato `Y-m-d\TH:i` (com a letra T literal entre data e hora). Foi correção rápida, mas é um deslize meio curioso porque é detalhe documentado da especificação HTML. Esperava que ela soubesse de primeira.

**O `git push` que não funcionou.** Quando pedi pra dar push depois da etapa 2, recebi erro 403 do GitHub. O Git Credential Manager da minha máquina estava autenticado com outro usuário (sem permissão no repositório `DerickSonda/trabalho.pet`). A IA reconheceu o erro e me orientou a resolver no Credential Manager, mas todos os pushes seguintes também falharam pelo mesmo motivo. Os commits ficaram acumulados no local até eu trocar a credencial. Esse problema, claro, não era da IA, mas vale registrar: ela não tem como adivinhar credenciais do meu sistema, e nem deve tentar.

**Repetição do prefixo `/trabalho.pet/public/` em todo lugar.** Como o XAMPP serve o projeto a partir de uma subpasta, todos os redirects e links absolutos têm esse prefixo escrito direto no código. Cheguei a perguntar se não havia alternativa mais elegante e a resposta foi honesta: "para um trabalho acadêmico que roda só local, hardcoded está OK; em produção isso viraria uma constante ou variável de ambiente". Em produção isso seria uma péssima ideia, mas pro escopo do trabalho aceitei.

**Polimento visual exigiu pedido explícito.** Na primeira passada o CSS ficou funcional, mas sem graça: cores blocadas, sem animação, fundo cinza chapado. Tive que pedir literalmente "deixa um pouco mais bonito" pra ela refazer com gradientes, sombras em camadas e variáveis CSS. Esse tipo de julgamento estético é uma área onde a IA não toma iniciativa por padrão. Acho que ela prefere não arriscar opinião e ficar no mínimo viável, talvez por ter sido treinada pra ser conservadora.

**Aspect-ratio nos cards.** Pequena, mas registro: na primeira versão dos cards de pet, a foto tinha `height: 180px` fixo. Em telas largas, ficava esticada. Tive que apontar isso e pedir pra trocar por `aspect-ratio: 4/3 + width: 100%`. Detalhe que um designer humano pegaria de cara.

## 5. Conclusões e Segurança

Não daria pra copiar e colar esse projeto direto pra produção, e isso vale a pena ser dito de forma clara. O sistema roda só em localhost, sem HTTPS de verdade (logo, cookies sem `Secure`), com credenciais do XAMPP padrão (`root` sem senha) e sem proteção contra CSRF nos formulários. O ponto do CSRF foi escolha de escopo minha: como o trabalho é acadêmico e roda local, não inclui token. Mas em produção, um atacante poderia montar uma página externa que dispara um POST de exclusão enquanto eu estiver logado, e o sistema atual aceitaria. Os formulários usam POST em vez de GET, o que evita o caso "link de exclusão clicado por engano", mas não substitui token CSRF.

Os três pontos que o enunciado pediu para eu comentar:

**SQL injection.** Tudo via PDO com prepared statements. Mesmo onde a query é montada dinamicamente (a tela de registros monta o `WHERE` em array conforme os filtros vêm), os valores entram como parâmetros nomeados (`:uid`, `:tipo`, `:ini`, `:fim`), nunca concatenados como string. A única coisa "concatenada" são os nomes de coluna do `WHERE`, e esses são fixos no código.

**XSS.** Toda variável dinâmica que sai no HTML passa pela função `escapar()`. Inclui o nome do usuário no header, descrições de registros, nomes de pet, mensagens flash, valores re-renderizados em formulários após erro. O único conteúdo gerado pelo usuário que tem caminho próprio é a foto do pet, e ela é validada antes de salvar (extensão, MIME real, tamanho).

**Hash de senha.** `password_hash($senha, PASSWORD_DEFAULT)` no cadastro e `password_verify` no login. No banco fica gravado um hash bcrypt no formato `$2y$10$...`, nunca a senha em claro. Olhei a tabela `usuarios` no phpMyAdmin pra confirmar.

A IA respeitou essas três regras desde a primeira etapa, sem precisar de lembrete a cada prompt. Isso me poupou bastante tempo. Mas é algo que eu *consigo* verificar olhando o código, e acho que é aí que mora a conclusão pessoal mais honesta deste relatório: a IA escreve rápido, e eu preciso ler tudo. Em dois momentos (o flash do logout e o formato da data) ela deslizou em coisas que pareciam triviais. Se eu tivesse aceitado o código sem testar, esses bugs entrariam em produção.

No fim, vejo a IA mais como um par de mãos do que como um colega que decide por mim. Ela acelera a escrita de código repetitivo (formulários, validações, queries básicas), oferece boas práticas de segurança quando o prompt deixa claro o que se espera, e documenta razoavelmente bem o que faz. O que ela *não* faz: testar o sistema rodando no navegador, tomar decisões de produto e escopo, adivinhar contexto da minha máquina. Pra um trabalho acadêmico onde eu preciso entender e justificar o que está no código, foi uma parceria útil. Mas a leitura crítica do que ela produz continua sendo minha responsabilidade, e foi nessa leitura que apanhei os deslizes que listei acima.
