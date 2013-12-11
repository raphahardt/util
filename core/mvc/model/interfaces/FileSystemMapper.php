<?php

namespace Djck\mvc\interfaces;

/**
 * Interface que define todos os Mappers que escrevem em arquivos.
 * Ela contem mais um método destroy() que apaga o arquivo.
 */
interface FileSystemMapper extends CommonMapper {
  public function destroy();
}