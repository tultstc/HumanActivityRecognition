import cv2

WHITE_COLOR = (255, 255, 255)
GREEN_COLOR = (0, 255, 0)


def draw_line(image, p1, p2, color):
    p1 = (int(p1[0]), int(p1[1]))
    p2 = (int(p2[0]), int(p2[1]))
    if p1 != (0,0) and p2 != (0,0):
        cv2.line(image, p1, p2, color, thickness=2, lineType=cv2.LINE_AA)

def draw_circle(image, p, color):
    p = (int(p[0]), int(p[1]))
    if p != (0,0):
        cv2.circle(image, p, 4, color, -1)


def find_person_indicies(scores):
    return [i for i, s in enumerate(scores) if s > 0.9]


def filter_persons(outputs):
    persons = {}
    p_indicies = find_person_indicies(outputs["instances"].scores)
    for x in p_indicies:
        desired_kp = outputs["instances"].pred_keypoints[x][:].to("cpu")
        persons[x] = desired_kp
    return (persons, p_indicies)

def check_null(value):
    return 0 if value is None else value

def draw_keypoints(person, img):
    l_eye = check_null(person[1])
    r_eye = check_null(person[2])
    l_ear = check_null(person[3])
    r_ear = check_null(person[4])
    nose = check_null(person[0])
    l_shoulder = check_null(person[5])
    r_shoulder = check_null(person[6])
    l_elbow = check_null(person[7])
    r_elbow = check_null(person[8])
    l_wrist = check_null(person[9])
    r_wrist = check_null(person[10])
    l_hip = check_null(person[11])
    r_hip = check_null(person[12])
    l_knee = check_null(person[13])
    r_knee = check_null(person[14])
    l_ankle = check_null(person[15])
    r_ankle = check_null(person[16])

    draw_line(img, (l_shoulder[0], l_shoulder[1]),
              (l_elbow[0], l_elbow[1]), GREEN_COLOR)
    draw_line(img, (l_elbow[0], l_elbow[1]),
              (l_wrist[0], l_wrist[1]), GREEN_COLOR)
    draw_line(img, (l_shoulder[0], l_shoulder[1]),
              (r_shoulder[0], r_shoulder[1]), GREEN_COLOR)
    draw_line(img, (l_shoulder[0], l_shoulder[1]),
              (l_hip[0], l_hip[1]), GREEN_COLOR)
    draw_line(img, (r_shoulder[0], r_shoulder[1]),
              (r_hip[0], r_hip[1]), GREEN_COLOR)
    draw_line(img, (r_shoulder[0], r_shoulder[1]),
              (r_elbow[0], r_elbow[1]), GREEN_COLOR)
    draw_line(img, (r_elbow[0], r_elbow[1]),
              (r_wrist[0], r_wrist[1]), GREEN_COLOR)
    draw_line(img, (l_hip[0], l_hip[1]), (r_hip[0], r_hip[1]), GREEN_COLOR)
    draw_line(img, (l_hip[0], l_hip[1]), (l_knee[0], l_knee[1]), GREEN_COLOR)
    draw_line(img, (l_knee[0], l_knee[1]),
              (l_ankle[0], l_ankle[1]), GREEN_COLOR)
    draw_line(img, (r_hip[0], r_hip[1]), (r_knee[0], r_knee[1]), GREEN_COLOR)
    draw_line(img, (r_knee[0], r_knee[1]),
              (r_ankle[0], r_ankle[1]), GREEN_COLOR)

    draw_circle(img, (l_eye[0], l_eye[1]), WHITE_COLOR)
    draw_circle(img, (l_eye[0], l_eye[1]), WHITE_COLOR)
    draw_circle(img, (r_eye[0], r_eye[1]), WHITE_COLOR)
    draw_circle(img, (l_wrist[0], l_wrist[1]), WHITE_COLOR)
    draw_circle(img, (r_wrist[0], r_wrist[1]), WHITE_COLOR)
    draw_circle(img, (l_shoulder[0], l_shoulder[1]), WHITE_COLOR)
    draw_circle(img, (r_shoulder[0], r_shoulder[1]), WHITE_COLOR)
    draw_circle(img, (l_elbow[0], l_elbow[1]), WHITE_COLOR)
    draw_circle(img, (r_elbow[0], r_elbow[1]), WHITE_COLOR)
    draw_circle(img, (l_hip[0], l_hip[1]), WHITE_COLOR)
    draw_circle(img, (r_hip[0], r_hip[1]), WHITE_COLOR)
    draw_circle(img, (l_knee[0], l_knee[1]), WHITE_COLOR)
    draw_circle(img, (r_knee[0], r_knee[1]), WHITE_COLOR)
    draw_circle(img, (l_ankle[0], l_ankle[1]), WHITE_COLOR)
    draw_circle(img, (r_ankle[0], r_ankle[1]), WHITE_COLOR)
