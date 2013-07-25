<?php
/**
 * User: thofle
 * Date: 25.07.13
 * Time: 18:21
 */

function extentsionGetCatFact(&$pDB)
{
  $query = $pDB->query('SELECT getRandomCatFact()');
  $result = $query->fetch_array(MYSQLI_NUM);

  if (strlen($result[0]) > 5)
  {
    return $result[0];
  }

  return null;
}

function extentsionSaveCatFact(&$pDB, $catFact)
{
  if (strlen($catFact) > 140)
  {
    return false;
  }

  $statement = $pDB->prepare('INSERT INTO `ext_catfacts` (`catFact`) VALUES(?)');
  $statement->bind_param('s', $catFact);
  return $statement->execute();
}