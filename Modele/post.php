<?php
// Model class for Post entity
// Represents a post in the system

class Post {
    private $id;
    private $idCreateur;
    private $subject;
    private $creationDate;
    private $textContent;
    private $imageContent;
    private $VideoContent;
    private $numberOfView;
    private $numberOfLike;
    private $numberOfDislike;

    public function __construct($id = null, $idCreateur = null, $subject = null, $creationDate = null, $textContent = null, $imageContent = null, $VideoContent = null, $numberOfView = null, $numberOfLike = null, $numberOfDislike = null ) {
        $this->id = $id;
        $this->idCreateur = $idCreateur;
        $this->subject = $subject;
        $this->creationDate = $creationDate;
        $this->textContent = $textContent;
        $this->imageContent = $imageContent;
        $this->VideoContent = $VideoContent;
        $this->numberOfView = $numberOfView;
        $this->numberOfLike = $numberOfLike;
        $this->numberOfDislike = $numberOfDislike;
    }

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getIdCreateur() { return $this->idCreateur; }
    public function setIdCreateur($idCreateur) { $this->idCreateur = $idCreateur; }

    public function getSubject() { return $this->subject; }
    public function setSubject($subject) { $this->subject = $subject; }

    public function getCreationDate() { return $this->creationDate; }
    public function setCreationDate($creationDate) { $this->creationDate = $creationDate; }

    public function getTextContent() { return $this->textContent; }
    public function setTextContent($textContent) { $this->textContent = $textContent; }

    public function getImageContent() { return $this->imageContent; }
    public function setImageContent($imageContent) { $this->imageContent = $imageContent; }

    public function getVideoContent() { return $this->VideoContent; }
    public function setVideoContent($VideoContent) { $this->VideoContent = $VideoContent; }

    public function getNumberOfView() { return $this->numberOfView; }
    public function setNumberOfView($numberOfView) { $this->numberOfView = $numberOfView; }

    public function getNumberOfLike() { return $this->numberOfLike; }
    public function setNumberOfLike($numberOfLike) { $this->numberOfLike = $numberOfLike; }

    public function getNumberOfDislike() { return $this->numberOfDislike; }
    public function setNumberOfDislike($numberOfDislike) { $this->numberOfDislike = $numberOfDislike; }
}