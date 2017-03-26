<?php

/**
 * =====================================
 * Classe: CalculaFrete 
 * Função: Calcular Preços e Prazos para os serviços dos Correios
 * versão: 2017-03-26 14:21
 * autor : wanderlei santana <sans.pds@gmail.com>
 *
 * Documentação fonte: https://www.correios.com.br/para-voce/correios-de-a-a-z/pdf/calculador-remoto-de-precos-e-prazos/manual-de-implementacao-do-calculo-remoto-de-precos-e-prazos
 *
 * Classe PHP que implementa o Calculo de Fretes 
 * para encomendas, através dos serviços dos correios.
 * 
 *
 * === COMO USAR ======================= 
 * 
 *    Exemplo 01:
 *    # setando dados 
 *    $_args = array( 
 *       'nCdServico' => ServicosCorreios::SEDEX,
 *       'sCepOrigem'=>'11680000', 'sCepDestino' => '82220000', 
 *       'nVlPeso' => '1', 'nVlComprimento' => 30, 
 *       'nVlAltura' => 15, 'nVlLargura' => 20  );
 *    
 *    # solicitando calculo
 *    $calculafrete = new CalculaFrete( $_args );
 *    
 *    # exibindo os dados
 *    print_r( $calculafrete->request() );
 *
 *    Exemplo 02:
 *    # alterando apenas o serviço da consulta acima 
 *    # para realizar o calculo para os mesmos CEPs
 *    $calculafrete->nCdServico = ServicosCorreios::PAC;
 *    print_r( $calculafrete->request() );
 *
 */

class ServicosCorreios{
    const SEDEX        = 40010; /* sem contrato */
    const SEDEX10      = 40215; /* sem contrato */
    const SEDEXACOBRAR = 40045; /* sem contrato */
    const PAC          = 41106; /* sem contrato */
}

class CalculaFrete
{

    /**
     * URL que deve ser chamada ao solicitar o calculo 
     * de precos e prazos junto aos correios
     * 
     * @var string
     */
    private $url = 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx' ;

    /**
     * Parametros aceitos pelo serviço.
     * Foram mantidos os mesmos "names" da documentação 
     * oficial.
     * 
     * @var array
     */
    private $arr_params = array(
        /* @var string - username da empresa solicitante */
        'nCdEmpresa' => null ,
        /* @var string - senha da empresa solicitante */
        'sDsSenha' => null ,
        /* @integer - CEP de 8 digitos numéricos do CEP de Origem da encomenta */
        'sCepOrigem' => null ,
        /* @integer - CEP de 8 digitos numéricos do CEP de Destino da encomenta */
        'sCepDestino' => null ,
        /* @float - peso aproximado do item em Kg. Aceito apenas 0.3kg, e de 1kg a 30kg */
        'nVlPeso' => null ,
        'nCdFormato' => 1 ,
        /* @integer - Comprimento do pacote em cm */
        'nVlComprimento' => null ,
        /* @integer - altura do pacote em cm */
        'nVlAltura' => null ,
        /* @integer - largura do pacite em cm */
        'nVlLargura' => null ,
        /* @Boolean - */
        'sCdMaoPropria' => 'N' ,
        /* @decimal - se optar por sedex a cobrar, declarar o valor do objeto aqui */
        'nVlValorDeclarado' => 0 ,
        /* @Boolean - */
        'sCdAvisoRecebimento' => 'N' ,
        /* @integer - codigo do servico desejado - sedex, sedex 10, pac, etc */
        'nCdServico' => null ,
        /* @integer - */
        'nVlDiametro' => 0 ,
        /* @string - */
        'StrRetorno' => 'xml' ,
        /* @integer - */
        'nIndicaCalculo' => 3
    );

    /**
     * Metodo Construtor
     * 
     * @param Array $args - (opcional) Array com dados a ser enviado aos correios
     */
    public function __construct( $args = null ){
        if(is_array($args))
            $this->init( $args );
    }

    /**
     * Metodo usado para iniciar o Objeto, passando como 
     * parametro todos os dados do pacote que pretende 
     * enviar aos correios para calcular o frete.
     * 
     * @param  Array $_params - Array com dados a ser enviado aos correios
     * @return void
     */
    public function init( $_params ){
        if( (!is_array($_params)) || (count($_params)==0) ) return;

        if( isset($_params['nCdEmpresa']) )          $this->arr_params['nCdEmpresa'] = trim($_params['nCdEmpresa']) ;
        if( isset($_params['sDsSenha']) )            $this->arr_params['sDsSenha'] = trim($_params['sDsSenha']) ;
        if( isset($_params['sCepOrigem']) )          $this->arr_params['sCepOrigem'] = trim($_params['sCepOrigem']) ;
        if( isset($_params['sCepDestino']) )         $this->arr_params['sCepDestino'] = trim($_params['sCepDestino']) ;
        if( isset($_params['nVlPeso']) )             $this->arr_params['nVlPeso'] = trim($_params['nVlPeso']) ;
        if( isset($_params['nVlComprimento']) )      $this->arr_params['nVlComprimento'] = trim($_params['nVlComprimento']) ;
        if( isset($_params['nVlAltura']) )           $this->arr_params['nVlAltura'] = trim($_params['nVlAltura']) ;
        if( isset($_params['nVlLargura']) )          $this->arr_params['nVlLargura'] = trim($_params['nVlLargura']) ;
        if( isset($_params['nVlValorDeclarado']) )   $this->arr_params['nVlValorDeclarado'] = trim($_params['nVlValorDeclarado']) ;
        if( isset($_params['nCdServico']) )          $this->arr_params['nCdServico'] = trim($_params['nCdServico']) ;
        if( isset($_params['url']) )                 $this->arr_params['url'] = trim($_params['url']) ;
        if( isset($_params['nCdFormato']) )          $this->arr_params['nCdFormato'] = trim($_params['nCdFormato']) ;
        if( isset($_params['sCdMaoPropria']) )       $this->arr_params['sCdMaoPropria'] = trim($_params['sCdMaoPropria']) ;
        if( isset($_params['sCdAvisoRecebimento']) ) $this->arr_params['sCdAvisoRecebimento'] = trim($_params['sCdAvisoRecebimento']) ;
        if( isset($_params['nVlDiametro']) )         $this->arr_params['nVlDiametro'] = trim($_params['nVlDiametro']) ;
        if( isset($_params['StrRetorno']) )          $this->arr_params['StrRetorno'] = trim($_params['StrRetorno']) ;
        if( isset($_params['nIndicaCalculo']) )      $this->arr_params['nIndicaCalculo'] = trim($_params['nIndicaCalculo']) ;

    }

    /**
     * Metodo usado para processar a requisicao de 
     * calculo aos correios.
     * 
     * @param Array $args - (opcional) Array com dados a ser enviado aos correios
     * @return Xml - retorna o XML com as informações.
     */
    public function request( $args = null )
    {
        if(is_array($args))
            $this->init( $args );

        $__url = $this -> url . '?' ;
        foreach ($this -> arr_params as $key => $value)
            $__url .= "$key=$value&" ;
        $__url = rtrim($__url, '&');

        $xml = simplexml_load_file( $__url );

        return $xml -> cServico ;
    }

    /**
     * Metodo magico para setar os dados do Array 
     * de informacoes enviadas para o calculo.
     * 
     * @param string $key - a informacao que deseja setar
     * @param Mixed $value - o valor para a informacao a ser setada
     */
    public function __set( $key, $value ){
        $this -> arr_params[$key] = $value;
    }

    /**
     * Metodo Magico get para obter dados do Objeto
     * 
     * @param  string $key - a informacao que deseja obter 
     * @return Mixed
     */
    public function __get($key){
        return $this -> arr_params[$key];
    }

}

