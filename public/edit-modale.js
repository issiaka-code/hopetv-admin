// ===== GESTION DU FORMULAIRE D'ÉDITION =====
$('input[name="media_type"]', "#editForm").change(function () {
    const selectedType = $(this).val();

    // Masquer toutes les sections
    $(
        "#editAudioFileSection, #editVideoFileSection, #editVideoLinkSection, #editPdfFileSection, #editImageFileSection"
    ).addClass("d-none");

    // Supprimer l'attribut required des inputs
    $(
        "#editAudioFile, #editVideoFile, #editVideoLink, #editPdfFile, #editImageFiles, #editImageCoverFile"
    ).removeAttr("required");

    // Afficher la section correspondant au type sélectionné
    if (selectedType === "audio") {
        $("#editAudioFileSection").removeClass("d-none");
    } else if (selectedType === "video_file") {
        $("#editVideoFileSection").removeClass("d-none");
    } else if (selectedType === "video_link") {
        $("#editVideoLinkSection").removeClass("d-none");
        $("#editVideoLink").attr("required", "required");
    } else if (selectedType === "pdf") {
        $("#editPdfFileSection").removeClass("d-none");
    } else if (selectedType === "images") {
        $("#editImageFileSection").removeClass("d-none");
    }
});

// Gestion des fichiers (audio, vidéo, pdf, image couverture)
$(
    "#editAudioFile, #editVideoFile, #editPdfFile, #editAudioImageFile, #editThumbnail, #editPdfImageFile, #editlinkImageFile"
).on("change", function () {
    let fileName = $(this).val().split("\\").pop();
    $(this)
        .next(".custom-file-label")
        .addClass("selected")
        .html(fileName || "Choisir un nouveau fichier");
});

// Gestion des images multiples
$("#editImageFiles").on("change", function () {
    const files = Array.from(this.files || []);
    const names = files.map((f) => f.name).filter(Boolean);
    let labelText =
        names.length === 0
            ? "Choisir des images"
            : names.length === 1
            ? names[0]
            : `${names.length} fichiers sélectionnés`;
    $(this).next(".custom-file-label").addClass("selected").html(labelText);

    const $customFile = $(this).closest(".custom-file");
    let $info = $customFile.next(".file-selected-info");
    if ($info.length === 0) {
        $info = $(
            '<div class="file-selected-info mt-1 small text-muted"></div>'
        );
        $customFile.after($info);
    }
    const maxShow = 5;
    const shown = names.slice(0, maxShow);
    const extra = names.length - shown.length;
    $info.html(
        extra > 0
            ? `Fichiers : ${shown.join(", ")} et +${extra} autre(s)`
            : `Fichiers : ${shown.join(", ")}`
    );
});

// Gestion image couverture des images multiples
$("#editImageCoverFile").on("change", function () {
    const fileName = $(this).val().split("\\").pop();
    $(this)
        .next(".custom-file-label")
        .addClass("selected")
        .html(fileName || "Choisir une image de couverture");
});

/**
 * Fonction générique pour gérer l'ouverture d'un modal d'édition
 */
function handleEditMedia(
    button,
    routeEdit,
    routeUpdate,
    modalSelector = "#editModal"
) {
    const entityId =
        button.data("temoignage-id") ||
        button.data("priere-id") ||
         button.data("prophetie-id") ||
          button.data("home-charity-id") ||
        button.data('media-id') ||
        button.data("podcast-id");

    if (!entityId) {
        console.error("Aucun ID trouvé sur le bouton");
        return;
    }

    $.ajax({
        url: routeEdit.replace(":id", entityId),
        method: "GET",
        success: function (data) {
            const modal = $(modalSelector);

            // Remplir les champs génériques
            modal.find("#editNom").val(data.nom);
            modal.find("#editDescription").val(data.description);

            // Définir la route d’update
            modal
                .find("form")
                .attr("action", routeUpdate.replace(":id", entityId));

            // Réinitialiser toutes les sections et radios
            modal
                .find(
                    "#editAudioFileSection, #editVideoFileSection, #editVideoLinkSection, #editPdfFileSection, #editImageFileSection"
                )
                .addClass("d-none");
            modal.find("input[type=radio]").prop("checked", false);
            modal.find(".btn-group .btn").removeClass("active");

            if (data.media) {
                const type = data.media.type;
                const fileName = data.media.url_fichier
                    ? data.media.url_fichier.split("/").pop()
                    : "";
                const thumbnailName = data.media.thumbnail
                    ? data.media.thumbnail.split("/").pop()
                    : "";

                switch (type) {
                    case "audio":
                        modal
                            .find("#editMediaTypeAudio")
                            .prop("checked", true)
                            .trigger("change");
                        modal
                            .find("#editMediaTypeAudioLabel")
                            .addClass("active");
                        modal.find("#editCurrentAudioName").text(fileName);
                        modal.find("#editCurrentAudio").show();
                        if (data.media.thumbnail) {
                            modal
                                .find("#editCurrentAudioThumbnailName")
                                .text(thumbnailName);
                            modal
                                .find("#editCurrentAudioThumbnailPreview")
                                .attr("src", "/storage/" + data.media.thumbnail)
                                .show();
                        }
                        break;

                    case "video":
                        modal
                            .find("#editMediaTypeVideoFile")
                            .prop("checked", true)
                            .trigger("change");
                        modal
                            .find("#editMediaTypeVideoFileLabel")
                            .addClass("active");
                        modal.find("#editCurrentVideoName").text(fileName);
                        modal.find("#editCurrentVideo").show();
                        if (data.media.thumbnail) {
                            modal
                                .find("#editCurrentThumbnailName")
                                .text(thumbnailName);
                            modal
                                .find("#editCurrentThumbnailPreview")
                                .attr("src", "/storage/" + data.media.thumbnail)
                                .show();
                            modal.find("#editCurrentThumbnail").show();
                        }
                        break;

                    case "link":
                        modal
                            .find("#editMediaTypeVideoLink")
                            .prop("checked", true)
                            .trigger("change");
                        modal
                            .find("#editMediaTypeVideoLinkLabel")
                            .addClass("active");
                        modal
                            .find("#editVideoLink")
                            .val(data.media.url_fichier);

                        modal
                            .find("#editCurrentLinkValue")
                            .text(data.media.url_fichier);    
                        modal
                            .find("#editfileCurrentLinkValue")
                            .text(data.media.thumbnail);
                        modal.find("#editCurrentLink").show();
                        break;

                    case "pdf":
                        modal
                            .find("#editMediaTypePdf")
                            .prop("checked", true)
                            .trigger("change");
                        modal.find("#editMediaTypePdfLabel").addClass("active");
                        modal.find("#editCurrentPdfName").text(fileName);
                        modal.find("#editCurrentPdf").show();
                        break;

                    case "images":
                        modal
                            .find("#editMediaTypeImages")
                            .prop("checked", true)
                            .trigger("change");
                        modal
                            .find("#editMediaTypeImagesLabel")
                            .addClass("active");
                        const container = modal
                            .find("#existingImagesContainer")
                            .empty();

                        let imgs = [];
                        try {
                            imgs =
                                JSON.parse(data.media.url_fichier || "[]") ||
                                [];
                        } catch (e) {
                            imgs = [];
                        }

                        if (imgs.length > 0) {
                            imgs.forEach((path) => {
                                const url = "/storage/" + path;
                                const id =
                                    "del_" +
                                    btoa(path).replace(/[^a-zA-Z0-9]/g, "");
                                const col = $(
                                    '<div class="col-6 col-md-4 col-lg-3 mb-2"></div>'
                                );
                                const card = $(
                                    '<div class="border rounded p-2 h-100"></div>'
                                );
                                card.append(
                                    `<img src="${url}" class="img-fluid mb-2" style="height:120px;object-fit:cover;width:100%" />`
                                );
                                card.append(`<div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="${id}" name="existing_images_delete[]" value="${path}">
                                    <label class="custom-control-label" for="${id}">Supprimer</label>
                                </div>`);
                                col.append(card);
                                container.append(col);
                            });
                        } else {
                            container.html(
                                '<div class="col-12"><p class="text-muted">Aucune image existante</p></div>'
                            );
                        }

                        if (data.media.thumbnail) {
                            const coverInfo = $(
                                `<div class="alert alert-info mt-2"><small><strong>Image de couverture actuelle :</strong><br>${thumbnailName}</small></div>`
                            );
                            modal
                                .find("#editImageCoverFile")
                                .closest(".form-group")
                                .append(coverInfo);
                        }
                        break;
                }
            }

            modal.modal("show");
        },
        error: function () {
            Swal.fire(
                "Erreur",
                "Impossible de charger les données du média.",
                "error"
            );
        },
    });
}

// Réinitialiser le modal à la fermeture
$("#editModal").on("hidden.bs.modal", function () {
    $("#editImageFiles").next(".custom-file-label").html("Choisir des images");
    $("#editImageCoverFile")
        .next(".custom-file-label")
        .html("Choisir une image de couverture");
    $(".file-selected-info").remove();
    $("#existingImagesContainer").empty();
    $("#editImageCoverFile")
        .closest(".form-group")
        .find(".alert-info")
        .remove();
    $(
        "#editMediaTypeAudioLabel, #editMediaTypeVideoFileLabel, #editMediaTypeVideoLinkLabel, #editMediaTypePdfLabel, #editMediaTypeImagesLabel"
    ).removeClass("active");
    $(
        "#editMediaTypeAudio, #editMediaTypeVideoFile, #editMediaTypeVideoLink, #editMediaTypePdf, #editMediaTypeImages"
    ).prop("checked", false);
});
