let addImageFilesDT = null;

$("#addstoreModal").on("show.bs.modal", function (event) {
    const button = $(event.relatedTarget);
    const modal = $(this);

    // Route dynamique
    const route = button.data("route");
    modal.find("#addstoreForm").attr("action", route);

    // Reset du formulaire
    const form = modal.find("#addstoreForm")[0];
    form.reset();

    // Reset accumulated images
    addImageFilesDT = null;

    // Types de média à afficher
    const mediaTypes = button.data("media-types")
        ? button.data("media-types").split(",")
        : [];

    // Cacher tous les boutons et réinitialiser l'état
    modal.find(".btn-group .btn").addClass("d-none").removeClass("active");
    modal.find(".btn-group .btn input[type=radio]").prop("checked", false);

    // Afficher seulement les types demandés
    const typeToLabelId = {
        audio: "#addMediaTypeAudioLabel",
        video_file: "#addMediaTypeVideoFileLabel",
        video_link: "#addMediaTypeVideoLinkLabel",
        pdf: "#addMediaTypePdfLabel",
        images: "#addMediaTypeImagesLabel",
    };
    mediaTypes.forEach((type) => {
        const labelId = typeToLabelId[type];
        if (labelId) $(labelId).removeClass("d-none");
    });

    // Activer le premier bouton visible
    const firstVisible = modal
        .find(".btn-group .btn:not(.d-none) input[type=radio]")
        .first();
    firstVisible.prop("checked", true);
    firstVisible.closest(".btn").addClass("active");

    // Afficher la section correspondante
    showMediaSection(firstVisible.val());
});

// Fonction pour afficher la section correspondante
function showMediaSection(type) {
    const sections = {
        audio: "#addAudioFileSection",
        video_file: "#addVideoFileSection",
        video_link: "#addVideoLinkSection",
        pdf: "#addPdfFileSection",
        images: "#addImageFileSection",
    };
    // Cacher toutes les sections
    Object.values(sections).forEach((s) => $(s).addClass("d-none"));
    // Supprimer les required de tous les inputs
    $("#addstoreForm input, #addstoreForm textarea").removeAttr("required");
    // Afficher la section correspondante et ajouter les required
    if (sections[type]) {
        $(sections[type]).removeClass("d-none");
        $(sections[type]).find("input, textarea").attr("required", "required");
    }
}

// Changement de type quand l'utilisateur clique sur un bouton
$("#addstoreModal .btn-group input[type=radio]").change(function () {
    const type = $(this).val();
    const $input = $(this); // le radio cliqué

    showMediaSection(type);
    const labelId = $input.closest("label").attr("id"); 
    const inputId = $input.closest("input").attr("id"); 
   
    $("#addstoreModal").find(inputId).addClass("active");
  
});

// Gestion des labels des fichiers (y compris images multiples)
$("#addstoreModal input[type=file]").on("change", function () {
    const isMultiple = $(this).attr("multiple") !== undefined;
    const files = Array.from(this.files || []);
    const names = files.map((f) => f.name).filter(Boolean);

    // Gestion spéciale pour images multiples
    if (this.id === "addImageFiles") {
        if (!addImageFilesDT) addImageFilesDT = new DataTransfer();
        files.forEach((f) => addImageFilesDT.items.add(f));
        this.files = addImageFilesDT.files;
    }

    // Déterminer le label par défaut
    const defaultLabel =
        {
            addImageFiles: "Choisir des images",
            addImageCoverFile: "Choisir une image de couverture",
            addlinkImageFile: "Choisir une image de couverture",
            addAudioImageFile: "Choisir une image",
            addVideoImageFile: "Choisir une image",
            addPdfImageFile: "Choisir une image",
            addAudioFile: "Choisir un fichier",
            addVideoFile: "Choisir un fichier",
            addPdfFile: "Choisir un fichier",
        }[this.id] || "Choisir un fichier";

    let labelText = defaultLabel;
    if (isMultiple) {
        if (names.length === 1) labelText = names[0];
        else if (names.length > 1)
            labelText = `${names.length} fichiers sélectionnés`;
    } else {
        labelText = names[0] || defaultLabel;
    }
    $(this).next(".custom-file-label").addClass("selected").html(labelText);

    // Afficher liste détaillée pour images multiples
    if (this.id === "addImageFiles") {
        let $info = $(this).closest(".custom-file").next(".file-selected-info");
        if ($info.length === 0) {
            $info = $(
                '<div class="file-selected-info mt-1 small text-muted"></div>'
            );
            $(this).closest(".custom-file").after($info);
        }
        if (names.length > 0) {
            const maxShow = 5;
            const shown = names.slice(0, maxShow);
            const extra = names.length - shown.length;
            $info.html(
                extra > 0
                    ? `Sélection : ${shown.join(", ")} et +${extra} autre(s)`
                    : `Sélection : ${shown.join(", ")}`
            );
        } else $info.empty();
    }
});

// Reset du modal à la fermeture
$("#addstoreModal").on("hidden.bs.modal", function () {
    const modal = $(this);
    modal.find("#addstoreForm")[0].reset();
    modal.find(".custom-file-label").html(function () {
        return $(this).hasClass("selected")
            ? $(this).data("default") || "Choisir un fichier"
            : "Choisir un fichier";
    });
    modal.find(".file-selected-info").empty();
    modal.find("#addMediaTypeAudio").prop("checked", true).trigger("change");
    addImageFilesDT = null;
});
