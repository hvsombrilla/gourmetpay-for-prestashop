{extends "$layout"}

{block name="content"}
  <section>
    <p>El formulario se envio</p>
    <p>Texto</p>
    <ul>
      {foreach from=$params key=name item=value}
        <li>{$name}: {$value}</li>
      {/foreach}
    </ul>
    <p>El pago se esta procesando</p>
  </section>
{/block}
