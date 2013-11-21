#{block 'page.title' prepend}#{$view.title}Teste - #{/block}
#{block 'page.contents'}
  <style>
  .aaa {
    background: url('#{$site.URL}/static/123.jpg') no-repeat 50% 50%;
    width:100%;
    height: 500px;
  }
  
  @media (max-width: 700px) {
    .aaa {
      background: url('#{$site.URL}/static/123-600.jpg') no-repeat 50% 50%;
    }
  }
  
  @media (max-width: 400px) {
    .aaa {
      background: url('#{$site.URL}/static/123-100.jpg') no-repeat 50% 50%;
    }
  }
  
  @media (max-width: 300px) {
    .aaa {
      background: url('#{$site.URL}/static/123-50x400.jpg') no-repeat 50% 50%;
    }
  }
  </style>
  <div class="aaa">
    
  </div>
#{/block}