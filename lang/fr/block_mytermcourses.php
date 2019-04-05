<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Initially developped for :
 * Université de Cergy-Pontoise
 * 33, boulevard du Port
 * 95011 Cergy-Pontoise cedex
 * FRANCE
 *
 * Displays the courses within which the user his enrolled, their categories,
 * and their teachers. Courses open to all users are also displayed.
 * Courses are displayed on two columns, one for each term in the year.
 * Teachers can move their courses to select the relevant column.
 *
 * @package    block_mytermcourses
 * @author     Brice Errandonea <brice.errandonea@u-cergy.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * File : lang/fr/block_mytermcourses.php
 * French text strings
 *
 */

$string['pluginname'] = 'Mes cours';
$string['mytermcourses'] = 'Mes cours';
$string['mytermcourses:addinstance'] = 'Ajouter un nouveau bloc Mes cours';
$string['mytermcourses:myaddinstance'] = 'Ajouter un nouveau bloc Mes cours à mon Tableau de bord';
$string['changetitle'] = 'Titre du bloc';
$string['bgcolor'] = 'Couleur de fond des catégories';
$string['teacher'] = 'Enseignant';
$string['teachers'] = 'Enseignants';
$string['useidnumber'] = 'Trier les cours par idnumber';
$string['commoncategories'] = 'Catégories communes';
$string['notenrolled'] = "Vous n'êtes inscrit à aucun cours";
$string['firstterm'] = 'Premier semestre';
$string['secondterm'] = 'Second semestre';
$string['configidnumber'] = 'le tri des cours par idnumber';
$string['configidnumber_help'] = 'Pour afficher les cours, les trier par idnumber et pas par sortorder.';
$string['configcommon'] = 'les catégories communes';
$string['configcommon_help'] = 'Les cours de ces catégories seront affichés pour tous les utilisateurs.'
        . ' Vous voudrez certainement vous assurer que ces cours sont bien ouverts aux utilisateurs non inscrits.'
        . ' Ecrire les id des catégories, avec des symboles ; pour les séparer.';
$string['addcourse'] = "Ajout d'un nouveau cours";
$string['myoldcourses'] = 'Mes anciens cours';
$string['similarnewcourses'] = 'Déjà récupéré';
$string['teacheroldcourses'] = 'Cours que vous donniez l\'an dernier';
$string['adminoldcourses'] = 'Cours que vous aidiez l\'an dernier';
$string['studentoldcourses'] = 'Cours que vous suiviez l\'an dernier';
$string['fetch'] = 'Transférer';
$string['fetchagain'] = 'Transférer à nouveau';
$string['cantseecourse'] = 'Je ne vois pas mon cours. Que faire ?';
$string['reallyfetchagain'] = 'Voulez-vous vraiment transférer ce cours à nouveau ?';
$string['alreadycreated'] = 'Déjà créé';
$string['createagain'] = 'Créer à nouveau';
$string['reallycreateagain'] = 'Il y a déjà un espace de cours Moodle pour ce même cours Apogée.'
        . ' Voulez-vous vraiment en créer un autre ?';
$string['chooseformat'] = 'Choisissez un format pour votre cours';
$string['choosecategory'] = 'Choisissez une catégorie de cours';
$string['choosefaculty'] = 'Choisissez une composante';
$string['chooselevel'] = 'Choisissez un niveau';
$string['choosetraining'] = 'Choisissez une formation (VET)';
$string['suggestedcohorts'] = 'Cohortes suggérées';
$string['mutualchooseany'] = 'Si le cours que vous créez est mutualisé entre plusieurs des formations ci-dessous,'
        . ' cliquez sur n\'importe laquelle des formations concernées.';
$string['categorynotfound'] = 'Vous ne trouvez pas la catégorie que vous cherchez ? Vous pouvez en déclarer une nouvelle.'
        . ' Mais évitez d\'utiliser ceci trop souvent : il vaut mieux déclarer les nouvelles catégories dans Apogée'
        . ' plutôt qu\'ici.';
$string['coursenotfound'] = 'Vous ne trouvez pas le cours que vous cherchez ? Vous pouvez en déclarer un nouveau.'
        . ' Mais évitez d\'utiliser ceci trop souvent : il vaut mieux déclarer les nouveaux cours dans Apogée'
        . ' plutôt qu\'ici.';
$string['createnewcategory'] = 'Créer une nouvelle catégorie';
$string['missingcategoryname'] = 'Vous devez donner un nom à la catégorie que vous créez.';
$string['missingcoursename'] = 'Vous devez donner un nom au cours que vous créez.';
$string['createwhichcourse'] = 'Lequel de ces cours souhaitez-vous créer ?';
$string['categoryplaceholder'] = 'Choisissez bien. Vous ne pourrez pas le modifier par la suite.';
$string['courseplaceholder'] = '';
$string['creatingcourse'] = 'Création du cours';
$string['incategory'] = 'dans la catégorie';
$string['cohortsare'] = 'Les "cohortes" d\'étudiants correspondent aux groupes Apogée.'
        . ' Si vous liez une cohorte à votre cours, chaque fois qu\'un étudiant sera ajouté à la cohorte (dans Apogée),'
        . ' il sera automatiquement ajouté au cours.';
$string['noknowncohorts'] = 'Nous n\'avons aucune information concernant les cohortes d\'étudiants'
        . ' que vous pourriez vouloir lier à ce cours.';
$string['choosecohorts'] = 'D\'après les informations dont nous disposons, ces cohortes peuvent vous intéresser.'
        . ' Merci de vérifier qu\'il n\'y a pas d\'erreur.';
$string['alllevelcohorts'] = 'Vous ne trouvez pas les cohortes que vous cherchez ?'
        . ' Cliquer ici pour voir toutes les cohortes de';
$string['tryenroldemands'] = "Vérifiez auprès de votre secrétariat pédagogique que vous êtes inscrit"
        . " dans le bon groupe Apogée et auprès de votre enseignant qu'il a bien lié ce groupe à ce cours."
        . " Sinon, cherchez dans la <a href='../course/index.php'>Liste complète des cours</a>."
        . " Vous pourrez peut-être déposer une demande d'inscription (si votre enseignant a activé cette possibilité).";


