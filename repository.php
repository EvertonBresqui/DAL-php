<?php

class Banco{  
    public function getConnection(){
        $pdo = new PDO('mysql:localhost;port=80;dbname=database', 'usuario', 'senha');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('SET NAMES utf8');
        //$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        //pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        return $pdo;
    }
    public function gravar($pdo, $tabela, $objcampos){
        try{
            $vetCampos = explode(',', json_encode($objcampos));
            $vetCampos = str_replace('[', '', $vetCampos);
            $vetCampos = str_replace(']', '', $vetCampos);
            $vetCampos = str_replace('"', '', $vetCampos);
            $nColunas = $pdo->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = 'transtudo' AND table_name = '$tabela'");
            $nColunas->execute();
            $query = $query2 = '';
            $vetCamposColunas = array();
            $vettipo = array();
            while($linha = $nColunas->fetch(PDO::FETCH_ASSOC)){
                $vettipo[] = $linha['DATA_TYPE'];
                $vetCamposColunas[] = $linha['COLUMN_NAME'];
            }      
            for($i = 0; $i < count($vetCampos); $i+= 1){
                if($vettipo[$i] == 'int')
                    $vetCampos[$i] = (int) $vetCampos[$i];
                else if($vettipo[$i] == 'bigint')
                    $vetCampos[$i] = (float) $vetCampos[$i];
                else if($vettipo[$i] == 'decimal')
                    $vetCampos[$i] = (float) $vetCampos[$i];
                else if($vettipo[$i] == 'float')
                    $vetCampos[$i] = (float) $vetCampos[$i];
            }
            if(count($vetCampos) == count($vetCamposColunas)){
                for($i = 0; $i < count($vetCamposColunas); $i += 1){
                    if($i != count($vetCamposColunas) - 1){
                        $query .= ":campo$i, ";                    
                    }
                    else{
                        $query .= ":campo$i";
                    }
                }
                for($i = 0; $i < count($vetCamposColunas); $i += 1){
                    if($i < count($vetCamposColunas) - 1){
                        $query2 .= $vetCamposColunas[$i]. ', ';
                    }
                    else{
                        $query2 .= $vetCamposColunas[$i];
                    }
                }
                $gravar = $pdo->prepare("INSERT INTO $tabela($query2) VALUES($query)");
                for($i = 0; $i < count($vetCamposColunas); $i += 1){
                    $gravar->bindValue(":campo$i", $vetCampos[$i]);                    
                }
                if($gravar->execute()){
                    $id = $pdo->lastInsertId();
                    return $id;
                }
                else{
                    return null;
                }
            }
        }
        catch(Exception $e){
        }
    }
    public function alterar($pdo, $tabela, $objcampos, $posPk, $valuePk)
    { //exemplo alterar("cliente","1",array('password'=> 'newpass','name'=> 'Ben');
       try{ 
            $vetCampos = explode(',', json_encode($objcampos));
            $vetCampos = str_replace('[', '', $vetCampos);
            $vetCampos = str_replace(']', '', $vetCampos);
            $vetCampos = str_replace('"', '', $vetCampos);
            $nColunas = $pdo->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = 'transtudo' AND table_name = '$tabela'");
            $nColunas->execute();
            $vetCamposColunas = array();
            $vettipo = array();
            $query = '';
            while($linha = $nColunas->fetch(PDO::FETCH_ASSOC)){
                $vettipo[] = $linha['DATA_TYPE'];
                $vetCamposColunas[] = $linha['COLUMN_NAME'];
            }      
            for($i = 0; $i < count($vetCampos); $i+= 1){
                if($vettipo[$i] == 'int')
                    $vetCampos[$i] = (int) $vetCampos[$i];
                else if($vettipo[$i] == 'bigint')
                    $vetCampos[$i] = (float) $vetCampos[$i];
            }
             if(count($vetCampos) == count($vetCamposColunas)){
                for($i = 0; $i < count($vetCamposColunas); $i += 1){
                    if($i != count($vetCamposColunas) - 1){
                        $query .= "$vetCamposColunas[$i] = :campo$i, ";                    
                    }
                    else{
                        $query .= "$vetCamposColunas[$i] = :campo$i";
                    }
                }
                $update = $pdo->prepare("UPDATE $tabela SET $query WHERE $posPk = :campoid");
                $update->bindValue(':campoid', $valuePk);
                for($i = 0; $i < count($vetCamposColunas); $i += 1){
                    $update->bindValue(":campo$i", $vetCampos[$i]);                    
                }
                if($update->execute()){
                    return true;
                }
                else{
                    return false;
                }
            }
       }
       catch(Exception $e){
       }
    }
    public function listar($pdo, $tabela, $coluna, $nome_indice, $valor)
    {//listar(pdo,'pessoas','PES_NOME|PES_SEXO','PES_COD',1);
        try {
                $sql = '';
                $query_vet = explode("|", $coluna);
                $cont_vet_query = count($query_vet);
                if($cont_vet_query != 1){
                    if(is_array($query_vet)){
                        $coluna = '';
                        for ($i = 0; $i < $cont_vet_query; $i++) {
                            if ($i == ($cont_vet_query - 1)) {
                                $coluna .= "$query_vet[$i]";
                            } else {
                                $coluna .= "$query_vet[$i],";
                            }
                        }
                    }
                }
                if($nome_indice != null && $valor != null){
                    $sql = $pdo->prepare("SELECT $coluna FROM $tabela WHERE $nome_indice LIKE :valor");
                    $sql->bindValue(':valor',$valor."%");
                }
                else{
                    $sql = $pdo->prepare("SELECT $coluna FROM $tabela");
                }
                if ($sql->execute()) {
                    $resultado = array();
                    if($linha = $sql->fetchAll(PDO::FETCH_ASSOC)) {
                        $resultado = $linha;
                    }
                    return $resultado;
                }
             else return false;

        } catch (Exception $e) {
        }
    }
    public function remover($pdo, $tabela, $nome_id, $value_id)
    {//REMOVER(PDO,'pessoas','pes_cod',1);
        try{
            $sql = "";
            $sql = $pdo->prepare("DELETE FROM $tabela WHERE $nome_id = :campo");
            $sql->bindValue(':campo', $value_id);
            if ($sql->execute()) {
                return true;
            }
            return false;
        }
        catch(Exception $e){
        }
    }
}
?>