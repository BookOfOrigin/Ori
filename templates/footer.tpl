    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.0.0-alpha1/jquery.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
    {if isset($javascript_files)} 
        {foreach $javascript_files as $script}
            <script src="/templates/js/{$script}.js" type="text/javascript"></script>
        {foreachelse}
        {/foreach} 
    {/if}
  </body>
</html>